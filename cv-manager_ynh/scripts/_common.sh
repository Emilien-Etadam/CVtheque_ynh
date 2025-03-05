#!/bin/bash

#=================================================
# COMMON VARIABLES
#=================================================

# dependencies used by the app
pkg_dependencies="php8.0-fpm php8.0-sqlite3 php8.0-gd php8.0-xml php8.0-zip php8.0-mbstring"

#=================================================
# PERSONAL HELPERS
#=================================================

# Check if directory exists and create it if necessary
create_dir_if_not_exists() {
    local dir=$1
    if [ ! -d "$dir" ]; then
        mkdir -p "$dir"
    fi
    chmod 750 "$dir"
    chown -R $app:www-data "$dir"
}

#=================================================
# EXPERIMENTAL HELPERS
#=================================================

#=================================================
# FUTURE OFFICIAL HELPERS
#=================================================
