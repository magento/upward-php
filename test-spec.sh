#!/bin/sh

set -e

function kill_child() {
    kill -KILL "$child"
}

function int_child() {
    kill -INT "$child"
}

function term_child() {
    kill -TERM "$child"
}

trap kill_child SIGKILL
trap int_child SIGINT
trap term_child SIGTERM

export UPWARD_PHP_UPWARD_PATH="$UPWARD_PATH"

# Test that config file can be found and loaded
php dev/bootstrap-controller.php > /dev/null

PORT=$(( ((RANDOM<<15)|RANDOM) % 63001 + 2000 ))

# Explicitly enable loading ENV variables (typically disabled in default php.ini)
php -S 127.0.0.1:${PORT} -d variables_order=EGPCS dev/router.php > /dev/null &
child=$!

sleep 1 # give PHP server a moment

echo "http://127.0.0.1:$PORT"
wait "$child"
