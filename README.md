# EmeraldCryptoCoin-PHP
Clase para comunicarse con el API JSON RPC de Emerald Crypto Coins usando PHP

Como usar
---------------
1. Incluir EmeraldCryptoCoin.php en tu script:

    ```php
    require_once('EmeraldCryptoCoin.php');
    ```
2. Crear un objeto EMD:

    ```php
    $EMD = new EMD('nombredeusuario','Contraseña');
    ```

    Puedes sobre escribir el host a usar, predeterminadamente se usa HTTP localhost en el puerto 12128.

    ```php
    $EMD = new EMD('username','password','localhost','12128');
    ```

    Si deseas crear una conexión segura (SSL) tu puedes incluir un certificado, de lo contrario, dejalo en blanco
    ```php
    $EMD->setSSL('/ruta/completa/a/micertificado.cert');
    ````

3. Usa todos los métodos que entrega el demonio de Emerald Crypto Coin. Algunos ejemplos:

    ```php
    $EMD->getinfo();
    
    $EMD->getrawtransaction('78b54347bf466dca38fdd891dc90eed90c1899b5febb4d6b546bad38fdce18be',1);
    
    $EMD->getblock('3eb5f799c1618fcb8de5a03f388e3f2b0579f16a3c7a5f73000a474815d3371a');
    ```

Información adicional
---------------
* Si el método falla por alguna razón, este retornará falso y podrás obtener el error en `$EMD->error`

* El codigo de estado HTTP lo puedes encontrar en $EMD->status y obtendrás un estado HTTP valido o retornará 0 si cURL no se puede conectar.

* La respuesta en versión bruta es almacenada en `$EMD->response` y el JSON en bruto es almacenado en `$EMD->raw_response`

