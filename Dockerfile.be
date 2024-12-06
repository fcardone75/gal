FROM wodby/php:8.2

WORKDIR /var/www/html
COPY . .

RUN touch .env
RUN echo "variables_order = \"EGPCS\"" > /usr/local/etc/php/conf.d/BNL_variables_order.ini    


EXPOSE  9000
