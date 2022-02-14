## Build

```sh
docker-compose up --build -d 
```
## Start / Stop

```sh
docker-compose up -d & docker-compose down
```

## Access Sh & migrate

```sh
docker-compose exec php /bin/bash && php bin/console doctrine:migrations:migrate
```



