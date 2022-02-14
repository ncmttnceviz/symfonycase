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

## Get Postman Collection

<a href='https://github.com/ncmttnceviz/symfonycase/blob/origin/postman_collection.json'> Collection </a>

