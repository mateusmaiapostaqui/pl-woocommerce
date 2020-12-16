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

	maskElements = Array.from(document.querySelectorAll('.postaqui_mask_zip_code'))
	maskElements.forEach(function(element){
		element.addEventListener('keyup', function(){
			postaquiMaskZip(element)
		}, false);
	});

});