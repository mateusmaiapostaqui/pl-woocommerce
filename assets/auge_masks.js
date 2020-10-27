jAuge = jQuery.noConflict();
jAuge.fn.as_mask = jQuery.fn.mask;

jAuge(document).ready(function(){

	jAuge(".as_mask_zip_code").as_mask('00000-000',{placeholder: "_____-___"});
	jAuge(".as_mask_date").as_mask('00/00/0000',{placeholder: "00/00/0000"});
	jAuge(".as_mask_small_qtd").as_mask('000.000',{reverse: true});
	jAuge(".as_mask_money").as_mask('000.000,00',{reverse: true});
	jAuge(".as_mask_percentage").as_mask('000,00',{reverse: true});
	jAuge(".as_mask_cnpj").as_mask('00.000.000/0000-00',{placeholder: "00.000.000/0000-00"});
	jAuge(".as_mask_cpf").as_mask('000.000.000-00',{placeholder: "000.000.000-00"});
	jAuge(".as_mask_bank_agency").as_mask('00000-0',{placeholder: "00000-0",reverse:true});
	jAuge(".as_mask_bank_account").as_mask('000.000.000',{placeholder: "000.000.000"});
	jAuge(".as_mask_bank_account_extended").as_mask('0.000.000.000-0',{placeholder: "0.000.000.000-0",reverse:true});
	jAuge(".as_mask_bank_digit").as_mask('0',{placeholder: "0"});
	jAuge(".as_mask_phone_prefix").as_mask('00',{placeholder: "00"});
	jAuge(".as_mask_phone_number").as_mask('Z0000-0000',{
		reverse: true,
		placeholder: "0000-0000",
		translation: {
			'Z': {
				pattern: /[0-9]/, optional: true
			}
		}
	}); 
	jAuge(".as_mask_full_phone").as_mask('(00) Z0000-0000',{
		reverse: false,
		placeholder: "(00) 0000-0000",
		translation: {
			'Z': {
				pattern: /[0-9]/, optional: true
			}
		}
	}); 	

});
