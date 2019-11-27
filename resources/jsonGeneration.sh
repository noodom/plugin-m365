#!/bin/bash

if [[ -f "$2" ]]; then
    sudo rm $2
fi
sudo python /var/www/html/plugins/m365/resources/jsonGeneration.py $1 $3 > $2
