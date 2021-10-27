<?php
		$setup_info['cao']['name'] = 'cao';
		$setup_info['cao']['title'] = 'Cao Schnittstelle';
		$setup_info['cao']['version'] = '16.1.0';
		$setup_info['cao']['app_order'] = 99;
		$setup_info['cao']['tables'] = ['egw_cao', 'egw_cao_meta'];
		$setup_info['cao']['enable'] = 1;

		//The application's hooks rergistered.
		$setup_info['cao']['hooks']['admin'] = 'cao_hooks::all_hooks';
		$setup_info['cao']['hooks']['sidebox_menu'] = 'cao_hooks::all_hooks';        /* Dependencies for this app to work */
		$setup_info['cao']['hooks']['search_link'] = 'cao_hooks::search_link';

		$setup_info['cao']['depends'][] = [
				 'appname'  => 'api',
				 'versions' => ['16.1'],
		];
