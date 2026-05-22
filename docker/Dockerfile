FROM php:8.3-fpm

# 更换为阿里云镜像源，避免网络问题
RUN sed -i 's/deb.debian.org/mirrors.aliyun.com/g' /etc/apt/sources.list.d/debian.sources 2>/dev/null || \
    sed -i 's/deb.debian.org/mirrors.aliyun.com/g' /etc/apt/sources.list 2>/dev/null || true

# 安装系统依赖
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libpq-dev \
    libedit2 \
    && rm -rf /var/lib/apt/lists/*

# 安装 PHP 扩展
RUN docker-php-ext-install \
    pdo \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# 安装 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 配置工作目录
WORKDIR /var/www/html

# 配置权限
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

EXPOSE 9000

CMD ["php-fpm"]
