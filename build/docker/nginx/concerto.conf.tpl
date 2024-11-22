server {
    listen {{nginx_port}} default_server;
    listen [::]:{{nginx_port}} default_server;

    root /app/leap/web;
    client_max_body_size 50M;
    rewrite ^/(.*)/$ /$1 permanent;

    {{nginx_server_conf}}

    location ~ /(\.|web\.config) {
        deny all;
    }
    location ~ ^{{base_dir}}bundles/leappanel/files/protected/ {
        deny all;
    }

    location ~ ^{{base_dir}}files/(protected|session)/ {
        rewrite ^{{base_dir}}(.*)$ {{base_dir}}app.php/$1 last;
    }

    location {{base_dir}}files {
        alias /app/leap/web/files;
    }

    location {{base_dir}}bundles {
        alias /app/leap/web/bundles;
    }

    location {{base_dir}}favicon.ico {
        alias /app/leap/web/favicon.ico;
    }

    location {{base_dir}} {
        try_files $uri {{base_dir}}app.php$is_args$args;
    }

    location ~ ^{{base_dir}}app\.php(/|$) {
        fastcgi_pass localhost:9000;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /app/leap/web/app.php;
        fastcgi_param DOCUMENT_ROOT /app/leap/web;
        fastcgi_param HTTPS off;
    }

    location ~ ^{{base_dir}}app_dev\.php(/|$) {
        fastcgi_pass localhost:9000;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /app/leap/web/app_dev.php;
        fastcgi_param DOCUMENT_ROOT /app/leap/web;
        fastcgi_param HTTPS off;
    }
}
