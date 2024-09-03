# Eccezioni

Le eccezioni contenute in questa cartella sono pensate per funzionare con ErrorLogger->handleException() e Box->execResponse(),
demandando a Box la loro gestione con un error_handler centralizzato.
Si possono usare come esempio per eccezioni specifiche in un progetto, che abbiano una path e un messaggio specifici (integrati o modificabili in fase di costruzione.)
A seconda di come è definita l'eccezione (codice di errore e messaggio interno, intese come proprietà dell'eccezione base di Php) si può istruire ErrorLogger sulle modalità di gestione della stessa (v. ErrorLogger->handleException).

Poichè contengono un responso che implementa l'interfaccia Box\Iresponse, Box->execResponse() può prenderne direttamente in carico il responso per restituire stati http e codici di errori appropriati. Nella pagina di atterraggio definita per la resa dell'errore, è possibile accedere al responso in corso verificando la presenza della variabile $Response.

Il responso base costruito è un'istanza di \Box\Response\StdResponse.


## Uso

E' possibile utilizzare le eccezioni integrate in diverse modi:

#### Creando un istanza con parametri


	throw new \Box\Exceptions\BadRequestException("Messaggio generico di bad request", 'path_to_file.php');

***

#### Utilizzando Box\Container
Si possono definire le eccezioni a piacere in una factory e creare eventualmente dei sottotipi.
In questo modo si possono personalizzare i parametri senza dover estendere un eccezione integrata:


	$App->factory("BadRequestException_1", function () {
	    
	    $e = new \Box\Exceptions\BadRequestException("Messaggio generico di bad request 1", 'path_to_file_1.php');
	
	    throw $e;
	
	});
	$App->create("BadRequestException_1");
	
	$App->factory("BadRequestException_2", function () {
	    
	    $e = new \Box\Exceptions\BadRequestException("Messaggio generico di bad request 2", 'path_to_file_2.php');
	
	    throw $e;
	
	});
	$App->create("BadRequestException_2");


Si può anche usare il container per definire il messaggio a runtime:


    $App->factory("BadRequestExceptionCustom", function ($message) {
       
        $e = new \Box\Exceptions\BadRequestException($message);
    
        throw $e;
    });
    $App->create("BadRequestExceptionCustom", 'Messaggio custom da inoltrare');
    
    $App->factory("NotFoundException", function ($message) {
       
        $e = new \Box\Exceptions\NotFoundException($message, 'path_to_not_found_view.php');
    
        throw $e;
    });
    $App->create("NotFoundException", 'Messaggio custom not found da inoltrare');



