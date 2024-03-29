INSERT INTO `Classificator_PaymentSystem` (`PaymentSystem_ID`, `PaymentSystem_Name`, `PaymentSystem_Priority`, `Value`, `Checked`) VALUES (NULL, 'Payme', '1', 'nc_payment_system_payme', '1');

CREATE TABLE IF NOT EXISTS `payme_transactions` (
			`transaction_id` bigint(11) NOT NULL AUTO_INCREMENT COMMENT 'идентификатор транзакции ',
			`paycom_transaction_id` char(25) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Номер или идентификатор транзакции в биллинге мерчанта. Формат строки определяется мерчантом.',
			`paycom_time` varchar(13) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'Время создания транзакции Paycom.',
			`paycom_time_datetime` datetime DEFAULT NULL COMMENT 'Время создания транзакции Paycom.',
			`create_time` datetime NOT NULL COMMENT 'Время добавления транзакции в биллинге мерчанта.',
			`perform_time` datetime DEFAULT NULL COMMENT 'Время проведения транзакции в биллинге мерчанта',
			`cancel_time` datetime DEFAULT NULL COMMENT 'Время отмены транзакции в биллинге мерчанта.',
			`amount` int(11) NOT NULL COMMENT 'Сумма платежа в тийинах.',
			`state` int(11) NOT NULL DEFAULT '0' COMMENT 'Состояние транзакции',
			`reason` tinyint(2) DEFAULT NULL COMMENT 'причина отмены транзакции.',
			`receivers` varchar(500) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'JSON array of receivers',
			 
			`cms_order_id` char(20) COLLATE utf8_unicode_ci NOT NULL COMMENT 'номер заказа CMS',
			 
			PRIMARY KEY (`transaction_id`),
			UNIQUE KEY `paycom_transaction_id` (`paycom_transaction_id`),
			UNIQUE KEY `cms_order_id` (`cms_order_id`,`paycom_transaction_id`),
			KEY `state` (`state`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2;