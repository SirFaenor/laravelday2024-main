
window.addEventListener('error', function (e) {

	// se c'è un errore rimuovo la class hidden dall'html così riesco a vedere la pagina
	this.dispatchEvent(window.ErrorGetLast);

	console.log('[ErrorHandler.js] ',e);
});
