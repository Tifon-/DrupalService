<?php

/**
 * Biblioteca que facilita la manteción de recursos de un Drupal que tenga
 * instalado el módulo Services.
 *
 * Requiere de Httpful
 *   http://phphttpclient.com/
 *   https://github.com/nategood/httpful
 *
 */

require(__DIR__ . '/httpful/bootstrap.php');

use \Httpful\Request;

/**
 * Clase DrupalService
 */
class DrupalService
{

  protected $CSRF_token = TRUE;

  /**
   * @var $url
   *   Concatena $base_url con $end_point.
   */
  protected $url = '';

  /**
   * @var $base_url
   *   Corresponde a la url base del sitio drupal.
   */
  protected $base_url = '';

  /**
   * @var $end_point
   *   Corresponde al endpoint configurado en el sitio de drupal.
   */
  protected $end_point = '';

  /**
   * @var mixed $user
   *   Cuando se identifica un usuario se deja registrado en esta variable.
   */
  protected $user = NULL;


  /**
   * @var mixed $cookie_session
   *   Es la estructura de la session en Drupal.
   */
  protected $cookie_session = NULL;


  /**
   * @param string $base_url
   *   Si el sitio de drupal no cuenta con clean url, se debe incluir "?q=" al
   *   final
   * @param string $end_point
   *   El endpoint configurado en el sitio de drupal
   * @param bool $CSRF_token
   *   Si el sitio de drupal funciona en la version 6, deberá ser falso.
   */
  public function __construct($base_url, $end_point, $CSRF_token = TRUE) {
    $this->base_url = $base_url;
    $this->end_point = $end_point;
    $this->url = $base_url . $end_point;
    $this->CSRF_token = $CSRF_token;
  }

  /**
   * Retorna al usuario identificado.
   */
  public function getLoggedUser() {
    return $this->user;
  }


  /**
   * Se identifica a un usuario.
   *
   * @param string $username
   *
   * @param string $password
   */
  public function login($username, $password) {
    $request_url = "$this->url/user/login.json";
    $user_data = array(
      'username' => $username,
      'password' => $password,
    );
    $response = Request::post($request_url)
      ->sendsJson()
      ->body(json_encode($user_data))
      ->send();

    if ($response->code == 200) {
      $logged_user = $response->body;
      // Registramos la cookie session para usarlo al consumir los recursos
      $this->cookie_session = $logged_user->session_name . '=' . $logged_user->sessid;
      $this->user = $logged_user;

      return $this;
    }
  }


  /**
   * Retorna la información de un recurso.
   *
   * @param string $resource
   *   Nombre del recurso, puede ser node, user, term, comment, etc. Todos
   *   aquellos que esten disponibles en el servicio.
   * @param int $id
   *   Identificador del recurso.
   */
  public function get($resource, $id) {
    $request_url = "$this->url/$resource/$id";

    $token = $this->getToken();

    $request = Request::get($request_url);

    if (!is_null($this->cookie_session)) {
      $request->addHeader('Cookie', $this->cookie_session);
    }

    return $this->response($request->send());
  }


  /**
   * Actualiza un recurso.
   *
   * @param string $resource
   *   Nombre del recurso
   * @param int $id
   *   Identificador del recurso
   * @param array $data
   *   Datos que se actualizarán del recurso.
   */
  public function update($resource, $id, Array $data) {
    $request_url = "$this->url/$resource/$id";

    $token = $this->getToken();

    $request = Request::put($request_url)
      ->sendsJson()
      ->addHeader('X-CSRF-Token', $token)
      ->body(json_encode($data));

    if (!is_null($this->cookie_session)) {
      $request->addHeader('Cookie', $this->cookie_session);
    }

    return $this->response($request->send());
  }

  /**
   * Crea un nuevo recurso.
   *
   * @param string $resource
   *   Nombre del recurso
   * @param array $data
   *   Datos que tendrá el nuevo recurso.
   */
  public function create($resource, $data) {
    $request_url = "$this->url/$resource";

    $token = $this->getToken();

    $request = Request::post($request_url)
      ->sendsJson()
      ->addHeader('X-CSRF-Token', $token)
      ->body(json_encode($data));

    if (!is_null($this->cookie_session)) {
      $request->addHeader('Cookie', $this->cookie_session);
    }

    return $this->response($request->send());
  }

  /**
   * Elimina un recurso
   *
   * @param string $resource
   *   Nombre del recurso
   * @param int $id
   *   Identificador del recurso.
   */
  public function delete($resource, $id) {
    $request_url = "$this->url/$resource/$id";

    $token = $this->getToken();

    $request = Request::delete($request_url)
      ->addHeader('X-CSRF-Token', $token);

    if (!is_null($this->cookie_session)) {
      $request->addHeader('Cookie', $this->cookie_session);
    }

    return $this->response($request->send());
  }


  /**
   * Interpreta el resultado de la solicitud.
   *
   * @param object $response
   *   Es la respuesta de la solicitud.
   */
  private function response($response) {
    if ($response->code == 200) {

      return $response->body;
    }

    throw new Exception($response->raw_headers);
  }

  /**
   * Retorna el token CSRF.
   *
   * Si el servicio es sobre un drupal version 6, no debería ejecutarse.
   */
  private function getToken() {
    if ($this->CSRF_token) {
      $request = Request::get($this->base_url . 'services/session/token');
      if (!is_null($this->cookie_session)) {
        $request->addHeader('Cookie', $this->cookie_session);
      }
      return $this->response($request->send());
    }
    else {
      return '';
    }
  }

}


