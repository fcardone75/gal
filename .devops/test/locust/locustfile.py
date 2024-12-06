from locust import HttpUser, TaskSet, task, between
from bs4 import BeautifulSoup
from dotenv import load_dotenv
import os


load_dotenv()

class CCLUser(HttpUser):
    wait_time = between(1, 3)
    host = os.getenv("HOST")

    @task
    def view_application_crud(self):
        self.client.get("/?crudAction=index&crudControllerFqcn=App%5CController%5CAdmin%5CApplicationCrudController")

    @task
    def view_application_group_420(self):
        self.client.get("/?crudAction=edit&crudControllerFqcn=App%5CController%5CAdmin%5CApplicationGroupCrudController&entityId=22")

    @task
    def view_application_detail(self):
        self.client.get("/?crudAction=detail&crudControllerFqcn=App%5CController%5CAdmin%5CApplicationCrudController&entityId=12&page=1&sort%5Bid%5D=DESC")

    @task
    def view_application_detail_2(self):
        self.client.get("/?crudAction=detail&crudControllerFqcn=App%5CController%5CAdmin%5CApplicationCrudController&entityId=152&page=1&sort%5Bid%5D=DESC")

    @task
    def view_application_group(self):
        self.client.get("/?crudAction=index&crudControllerFqcn=App%5CController%5CAdmin%5CApplicationGroupCrudController")

    def on_start(self):
        """Called when a Locust user starts, before any task is scheduled"""
        self.login()

    def on_stop(self):
        """Called when a Locust user stops, after all tasks are finished"""
        self.logout()

    def logout(self):
        # Effettua il logout
        response = self.client.get("/logout")
        if response.status_code == 200:
            print("Logout successful")
        else:
            print("Logout failed")

    def login(self):
        # Ottieni la pagina di login per estrarre il token CSRF
        response = self.client.get("/login")
        soup = BeautifulSoup(response.text, 'html.parser')
        csrf_token = soup.find('input', {'name': '_csrf_token'})['value']

        # Effettua il login con il token CSRF
        login_data = {
            'username': os.getenv("USERNAME"),
            'password': os.getenv("PASSWORD"),
            '_csrf_token': csrf_token,
            '_target_path': '/'
        }
        response = self.client.post("/login", data=login_data, headers={'Content-Type': 'application/x-www-form-urlencoded'})

        # Controlla se il login è avvenuto con successo
        if response.status_code == 200:
            print("Login successful")
        else:
            print("Login failed")

    @task(3)
    def eb_health_check(self):
        self.client.get("/elbHealthCheck")
