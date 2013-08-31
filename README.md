DrupalService
=============

Biblioteca para consumir servicios de Drupal que use el módulo Services y REST.

## Requerimiento

Biblioteca [Httpful](https://github.com/nategood/httpful)

PHP 5.3+

## Uso

La biblioteca facilia la creación de Nodos, usuarios, terminos, etc.. Todas las
entidades básicas que disponibiliza el módulo Services de Drupal.

## Ejemplo

### Creando un Nodo

```php
    require_once 'DrupalService.php';
    
    ...
    // Iniciamos identificando el sitio y su endpoint.
    $service = new DrupalService('http://drupal-7-site-services', 'endpoint');
    
    // Nos identificamos con un usuario que tenga permisos de creación
    $service->login($username, $password);
    
    // Generamos la estructura del nodo
    $node = new stdClass();
    $node->title = 'A node created with services 3.x and REST server';
    $node->type = 'page';
    $node->body['und'][0]['value'] = '<p>Hellyeah</p>';
    $node_data = (array) $node;
    
    // Ejecutamos el método crear del nodo.
    $service->create('node', $node_data);
```

Así como el método create existen el método get, update y delete:

```php
    require_once 'DrupalService.php';
 
    ...
    
    // Obtenemos un nodo
    $service->get('node', $nid);
    
    ...
    
    // Actualizamos un nodo
    $service->update('node', $node_data);
    
    ...
    
    // Eliminamos un nodo
    $service->delete('node', $nid);
    
```