#!/usr/bin/env python3
"""
Pipeline de teste ESTRUTURAL usando a API do MuApi (modo sandbox).

Objetivo: validar submit -> poll -> download -> concat funcionando de ponta
a ponta, sabendo que em sandbox os vídeos retornados são MOCK/aleatórios
(thumbnails de exemplo do modelo), não geração real.

Requisitos:
    pip install requests
    ffmpeg instalado e no PATH

Uso:
    export MUAPI_API_KEY="sua_chave_sandbox_aqui"   # criada com is_test=true
    python video_pipeline_muapi.py

Quando quiser trocar pra geração real, troque a chave sandbox por uma paga
e ajuste ENDPOINT pro modelo real que você quer usar (ex: com suporte a
image-to-video pra travar o produto). Nada mais no fluxo muda.
"""

import os
import time
import subprocess
from pathlib import Path

import requests

# ---------------------------------------------------------------------------
# CONFIGURAÇÃO
# ---------------------------------------------------------------------------

API_BASE = "https://api.muapi.ai/api/v1"
# Endpoint de exemplo (texto->vídeo). Troque pelo modelo que quiser testar
# ("veo3-fast-text-to-video", "minimax-h3-text-to-video", "seedance-2.5-text-to-video", etc.)
ENDPOINT = "veo3-fast-text-to-video"

OUTPUT_DIR = Path("pipeline_output_muapi")
FINAL_OUTPUT = "comercial_teste_estrutural.mp4"
POLL_INTERVAL_SECONDS = 5
POLL_TIMEOUT_SECONDS = 300

STYLE_LOCK = (
    "Premium photoreal CGI, vertical 9:16, warm amber-brown tones matching "
    "roasted coffee, cinematic beverage-studio lighting."
)

SEGMENTS = [
    {
        "name": "01_abertura_graos",
        "prompt": f"{STYLE_LOCK} Roasted coffee beans cascade in slow motion toward the lens.",
    },
    {
        "name": "02_impacto_produto",
        "prompt": f"{STYLE_LOCK} The coffee jar races into frame through swirling roasted beans.",
    },
    {
        "name": "03_reveal_label",
        "prompt": f"{STYLE_LOCK} Snap-zoom onto the label, the jar rotates revealing the full label.",
    },
    {
        "name": "04_packshot_final",
        "prompt": f"{STYLE_LOCK} Final lockoff hero packshot, jar centered and still with a mist halo.",
    },
]

# ---------------------------------------------------------------------------
# PIPELINE
# ---------------------------------------------------------------------------


def get_headers() -> dict:
    # Mesmo esquema de auth usado em app/Services/MuApiService.php (header x-api-key).
    api_key = os.environ.get("MUAPI_API_KEY")
    if not api_key:
        raise RuntimeError("Defina a variável de ambiente MUAPI_API_KEY antes de rodar.")
    return {"x-api-key": api_key, "Content-Type": "application/json"}


def submit_job(prompt: str) -> str:
    resp = requests.post(
        f"{API_BASE}/{ENDPOINT}",
        headers=get_headers(),
        json={"prompt": prompt},
        timeout=30,
    )
    resp.raise_for_status()
    data = resp.json()
    request_id = data.get("request_id") or data.get("data", {}).get("request_id")
    if not request_id:
        raise RuntimeError(f"Resposta sem request_id: {data}")
    return request_id


def poll_job(request_id: str, label: str) -> dict:
    url = f"{API_BASE}/predictions/{request_id}/result"
    waited = 0
    while waited < POLL_TIMEOUT_SECONDS:
        resp = requests.get(url, headers=get_headers(), timeout=30)
        resp.raise_for_status()
        data = resp.json()
        status = data.get("status")
        print(f"[{label}] status: {status}")
        if status in ("completed", "succeeded", "success"):
            return data
        if status == "failed":
            raise RuntimeError(f"[{label}] job falhou: {data}")
        time.sleep(POLL_INTERVAL_SECONDS)
        waited += POLL_INTERVAL_SECONDS
    raise TimeoutError(f"[{label}] excedeu {POLL_TIMEOUT_SECONDS}s esperando resultado.")


def extract_video_url(result: dict) -> str:
    # A forma exata do campo varia por modelo/mock; tenta os formatos mais comuns.
    if "outputs" in result and result["outputs"]:
        out = result["outputs"][0]
        return out if isinstance(out, str) else out.get("url")
    if "video" in result and isinstance(result["video"], dict):
        return result["video"].get("url")
    if "video_url" in result:
        return result["video_url"]
    raise RuntimeError(f"Não achei URL de vídeo na resposta: {result}")


def download(url: str, out_path: Path):
    resp = requests.get(url, timeout=60)
    resp.raise_for_status()
    out_path.write_bytes(resp.content)


def concat_videos(video_paths: list[Path], output_path: Path):
    list_file = OUTPUT_DIR / "concat_list.txt"
    with open(list_file, "w") as f:
        for p in video_paths:
            f.write(f"file '{p.resolve()}'\n")
    subprocess.run(
        [
            "ffmpeg", "-y", "-f", "concat", "-safe", "0",
            "-i", str(list_file), "-c", "copy", str(output_path),
        ],
        check=True,
        capture_output=True,
    )


def main():
    OUTPUT_DIR.mkdir(exist_ok=True)
    video_paths: list[Path] = []

    for seg in SEGMENTS:
        name = seg["name"]
        print(f"\n[{name}] enviando job...")
        request_id = submit_job(seg["prompt"])
        print(f"[{name}] request_id: {request_id}")
        result = poll_job(request_id, name)
        video_url = extract_video_url(result)
        print(f"[{name}] baixando: {video_url}")

        out_path = OUTPUT_DIR / f"{name}.mp4"
        download(video_url, out_path)
        video_paths.append(out_path)
        print(f"[{name}] salvo em {out_path}")

    final_path = OUTPUT_DIR / FINAL_OUTPUT
    concat_videos(video_paths, final_path)
    print(f"\nEstrutura validada. Vídeo (mock) final: {final_path.resolve()}")
    print("Lembre-se: esses clipes são aleatórios/exemplo do sandbox, não geração real.")


if __name__ == "__main__":
    main()
