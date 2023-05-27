# Stage 1: Build Symfony application
FROM composer AS composer
WORKDIR /app
COPY . /app
RUN composer install

# Stage 2: Create the final container
FROM nginx:latest
COPY --from=composer /app /var/www/html/projet

# Copy Nginx configuration file
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Expose port 80 for HTTP
EXPOSE 80

# Start Nginx service
CMD ["nginx", "-g", "daemon off;"]
