#!/bin/bash

CHUNKS=$(php ./src/index.php processor get_chunks)

for (( c=1; c<=$CHUNKS; c++ ))
do
	php src/index.php processor run $c &
done