FROM php:7.4-fpm-alpine

LABEL maintainer="epay"
LABEL description="彩虹易支付系统 - PHP Payment Gateway"

# 安装系统依赖和 PHP 扩展
RUN set -eux; \
    apk add --no-cache \
        freetype-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        gmp-dev \
        curl-dev \
        libsodium-dev \
        oniguruma-dev \
        libxml2-dev \
        tzdata \
    ; \
    # 安装 PHP 扩展
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mysqli \
        curl \
        mbstring \
        gd \
        gmp \
        bcmath \
        simplexml \
        opcache \
    ; \
    docker-php-ext-install -j$(nproc) sodium; \
    rm -rf /var/cache/apk/*

# 配置时区
ENV TZ=Asia/Shanghai
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# 配置 PHP
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php-custom.ini /usr/local/etc/php/conf.d/zz-epay.ini

# 设置工作目录
WORKDIR /var/www/html

# 复制项目文件
COPY --chown=www-data:www-data . /var/www/html/

# 创建必需的目录并设置权限
RUN set -eux; \
    mkdir -p \
        assets/uploads \
        plugins/alipay/cert \
        plugins/wxpay/cert \
        plugins/wxpayn/cert \
        plugins/wxpayng/cert \
        plugins/wxpaynp/cert \
        plugins/douyinpay/cert \
    ; \
    chown -R www-data:www-data \
        assets/uploads \
        plugins \
        install \
        admin \
    ; \
    chmod -R 755 assets/uploads plugins install admin

# 复制入口脚本
COPY docker/docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
