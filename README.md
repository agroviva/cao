# CAO-Faktura 1.5 Schnittstelle für EGroupware

# Clone Github Repository
```$``` ```git clone https://github.com/agroviva/cao```
# Install SSH2 Extension
```$``` ```sudo apt-get install php7.0-ssh2```
# Nginx Configuration
```
location /egroupware/cao/graph {
    alias /usr/share/egroupware/cao/graph;
    try_files $uri $uri/ @caograph;

    location ~ \index.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $request_filename;
        fastcgi_pass $egroupware:9000;
    }
}

location @caograph {
    rewrite /graph/(.*)$ /egroupware/cao/graph/index.php?/$1 last;
}
```
