FROM locustio/locust

# Installare BeautifulSoup
RUN pip install beautifulsoup4 \
    pip install python-dotenv

# Copia il file di test nella directory di lavoro del container
COPY locustfile.py /mnt/locust/locustfile.py
COPY .env /mnt/locust/.env

