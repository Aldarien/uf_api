<?php
return [
		'uf' => [
				'valoruf' => [
					'url' => 'http://valoruf.cl/',
					'part' => 'valores_anuales_uf_<year>.html',
					'class' => \UF\API\Provider\ValorUFGetter::class
				],
				'sii' => [
					'url' => 'http://www.sii.cl/pagina/valores/uf/',
					'part' => 'uf<year>.htm',
					'class' => \UF\API\Provider\SIIGetter::class
				]
		]
];
?>
