#!/usr/bin/env bash
# Publica el sitio Pro Fiesta en SiteGround via SSH/SCP.
# Uso:
#   ./deploy.sh              -> sube index.html
#   ./deploy.sh archivo ...  -> sube los archivos indicados
set -euo pipefail

KEY="$HOME/.ssh/profiesta_siteground"
HOST="gcam1045.siteground.biz"
USER="u2474-w5q9097hvz4t"
PORT="18765"
REMOTE_DIR="~/www/profiesta.com.mx/public_html"

FILES=("$@")
if [ ${#FILES[@]} -eq 0 ]; then
  FILES=("index.html")
fi

echo "Publicando en profiesta.com.mx: ${FILES[*]}"
scp -i "$KEY" -P "$PORT" "${FILES[@]}" "$USER@$HOST:$REMOTE_DIR/"
echo "Listo. Verifica en https://profiesta.com.mx/"
