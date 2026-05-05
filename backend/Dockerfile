FROM php:8.2-cli

RUN apt-get update \
	&& apt-get install -y --no-install-recommends git unzip libonig-dev libxml2-dev \
	&& docker-php-ext-install mbstring xml \
	&& rm -rf /var/lib/apt/lists/*

WORKDIR /app

