<h1>Invoice Payment Module</h1>

<h3>Установка</h3>

1. Скачайте [плагин](https://github.com/Invoice-LLC/Invoice.Module.HostCMS/archive/master.zip)
2. Перейдите во вкладку **Конетент->Интернет-магазины**, выберите ваш магазин, затем перейдите в **Справочники->Платежные системы**
3. Нажмите "Добавить", в названии впишите - Invoice, валюта - рубли, затем нажмите "Применить"
4. Перейдите в редактирование платежной системы, выберите статус заказа "Доставлено" и в поле "Обработчик" вставьте код из файла plugin.php
5. В названии класса Shop_Payment_System_HandlerXX замените XX на идентификатор платежной системы(**Вкладка "Дополнительные"**)
6. Введите свои значения переменных $api_key(API ключ из ЛК Invoice), $login(ID компании из ЛК Invoice), $terminal_name(Название терминала по умолчанию)
Пример:
```php
protected $api_key = "1526fec01b5d11f4df4f2160627ce351"; //API Key (можно получить в ЛК Invoice)
protected $login = "c24360cfac0a0c40c518405f6bc68cb0"; //Ваш Merchant ID (можно получить в ЛК Invoice)
protected $terminal_name = "HostCMS Terminal"; //Название терминала по умолчанию(Н-р: "Магазин на диване")
```
<br>Api ключ и Merchant Id:<br>
![image](https://user-images.githubusercontent.com/91345275/196218699-a8f8c00e-7f28-451e-9750-cfa1f43f15d8.png)
![image](https://user-images.githubusercontent.com/91345275/196218722-9c6bb0ae-6e65-4bc4-89b2-d7cb22866865.png)<br>

7. Добавьте уведомление в личном кабинете Invoice(Вкладка Настройки->Уведомления->Добавить)
   с типом **WebHook** и адресом: **%URL сайта%/shop/cart/**<br>
   ![Imgur](https://imgur.com/lMmKhj1.png)
