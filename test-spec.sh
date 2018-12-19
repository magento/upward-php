#!/bin/sh

PORT=$(( ((RANDOM<<15)|RANDOM) % 63001 + 2000 ))
export UPWARD_PHP_UPWARD_PATH="$UPWARD_PATH"
echo "http://0.0.0.0:$PORT"
exec php -S 0.0.0.0:${PORT} bin/upward > /dev/null
