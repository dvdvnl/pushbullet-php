# Pushbullet API client for PHP
Send pushes from PHP to Pushbullet users/channels.  
Full API documentation available [here](https://docs.pushbullet.com).

# Implementations
## Push
### Available types
* note
* link

### Function call
```
$pb = new Pushbullet($token);
$pb->pushLink($target, $title, $url, $body);
$pb->pushNote($target, $title, $body);
```
