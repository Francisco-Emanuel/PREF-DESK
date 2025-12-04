# Usa a imagem oficial do PHP 8.2 com FPM
FROM php:8.2-fpm

# Argumentos definidos no docker-compose.yml
ARG user
ARG uid

# 1. Instala dependências do sistema
# Adicionamos 'libzip-dev' que é necessário para a extensão zip
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Limpa o cache do apt
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Instala extensões do PHP
# Adicionamos 'zip' na lista
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Obtém o Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Cria o usuário do sistema
RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

# Define o diretório de trabalho
WORKDIR /var/www

# Define o usuário padrão
USER $user

# 3. Corrige o erro de "Dubious Ownership" do Git
# Isso diz ao Git dentro do container para confiar nesta pasta
RUN git config --global --add safe.directory /var/www