#!/bin/sh

PORT=$(( ((RANDOM<<15)|RANDOM) % 63001 + 2000 ))

echo "http://localhost:$PORT"
php -S localhost:${PORT} bin/upward > /dev/null
