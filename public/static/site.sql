DROP TABLE IF EXISTS `cloud_configs`;
CREATE TABLE `cloud_configs`(
    `k` varchar(255) NOT NULL DEFAULT '',
    `v` text,
    PRIMARY KEY (`k`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

INSERT INTO `cloud_configs` (`k`, `v`) VALUES
('vip_price_1', '1'),
('vip_price_2', '3'),
('vip_price_3', '5'),
('vip_price_4', '8'),
('quota_price_1', '1'),
('quota_price_2', '3'),
('quota_price_3', '5'),
('quota_price_4', '8'),
('agent_price_1', '10'),
('agent_price_2', '20'),
('agent_price_3', '50'),
('agent_give_z_1', '9'),
('agent_give_z_2', '8'),
('agent_give_z_3', '7'),
('site_price_1', '7'),
('site_price_2', '15'),
('site_price_3', '30'),
('site_price_4', '50');
