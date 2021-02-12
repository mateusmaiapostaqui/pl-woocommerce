document.addEventListener('DOMContentLoaded', function () {

	/**
	 * Mask zip on 00000-000 format
	 * @param {Node} input
	 * @return {void}
	 */
	function postaquiMaskZip(input){

		var value = input.value;
			value = value.replace(/\D/g,"");
			value = value.replace(/^(\d{5})(\d)/,"$1-$2");

		input.value = value;

	}

	// Input mask
	maskElements = Array.from(document.querySelectorAll('.postaqui_mask_zip_code'))
	maskElements.forEach(function(element){
		element.addEventListener('keyup', function(){
			postaquiMaskZip(element)
		}, false);
	});

	// Ajax shipping quote
	form = document.querySelector('.postaqui_product_form form');

	if( form ){
		form.addEventListener('submit', function(e){
			e.preventDefault();

			var data = new URLSearchParams(new FormData(form));
			var input = form.querySelector('input[name="postaqui_forecast_zip_code"]');
			var button = form.querySelector('button[type="submit"]');

			if( input.value == "" ){
				return;
			}

			button.disabled = true;
			button.innerHTML = 'Calculando...'

			fetch(form.action, {
				method: form.method,
				body: data
			})
			.then(function(response){
				return response.text()
			})
			.then(function(response){

				var newHtml = document.createElement("html")
					newHtml.innerHTML = response;

				var parent = form.parentElement;
				var currentResults = parent.querySelector('.postaqui_product_rates')
				var newResults = newHtml.querySelector('.postaqui_product_rates')

				parent.replaceChild(newResults, currentResults);

			}).finally(function(){

				button.disabled = false;
				button.innerHTML = 'Calcular Frete'

			});

		});
	}

});