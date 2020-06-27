## Requirements
> PHP: >=7.1.0 

### Extension
> Redis, Memcache, Http, Curl, Json, Mbstring

### Dependencies
> Composer

## Install
```composer require pavelzotikov/bot```

## Init class
```
class Bot extends \Bot\Handler
```
## Route
```
['GET|POST', '/bot', [Bot::class => 'execute']]
```
## Add Commands
Команда `/start` или слово `Привет`:
```
public function commandStart(string $service_name, string $chat_id)
```
## Add Catcher
Как отловить ответ пользователя на команду `/filter` или слово `Фильтр`:
```
public function commandFilter(string $service_name, string $chat_id)
{
    $this->startCatcher($service_name, $chat_id);

    return 'Напишите слово для фильтрации обьвлений.';
}
```
```
public function catcherFilter(string $service_name, string $chat_id, array $data)
{
    $this->stopCatcher($service_name, $chat_id);

    // здесь должен быть ваш код обработки ответа пользователя

    return sprintf('Фильтр по слову «%s» успешно добавлен.', $message);
}
```
## Add Default Catcher
Как отловить сообщения пользователя, если catcher не запущен
```
public function catcherDefault(string $service_name, string $chat_id, string $message)
{
    return 'Сюда попадут сообщения gjkmpjkdfnz'
}
```
