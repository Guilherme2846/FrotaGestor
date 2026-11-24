SET NAMES utf8mb4;

CREATE TABLE `abastecimentos` (
  `id` int(11) NOT NULL,
  `id_veiculo` int(11) NOT NULL,
  `id_motorista` int(11) DEFAULT NULL,
  `id_operacao` int(11) DEFAULT NULL,
  `id_fornecedor` int(11) DEFAULT NULL,
  `data_abastecimento` date NOT NULL,
  `tipo_diesel` enum('S10','S500','Outro') NOT NULL DEFAULT 'S500',
  `litros` decimal(11,3) NOT NULL,
  `valor_total` decimal(12,2) NOT NULL,
  `valor_litro` decimal(10,4) DEFAULT NULL,
  `arla_litros` decimal(11,3) NOT NULL DEFAULT 0.00,
  `arla_valor_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `arla_valor_litro` decimal(10,4) DEFAULT NULL,
  `km_base_manual` decimal(12,2) DEFAULT NULL,
  `km_anterior` decimal(12,2) DEFAULT NULL,
  `km_atual` decimal(12,2) NOT NULL,
  `km_rodado` decimal(12,2) DEFAULT NULL,
  `media_km_l` decimal(10,3) DEFAULT NULL,
  `observacao` text DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `fornecedores` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `tipo` enum('posto','oficina','borracharia','pecas','outro') NOT NULL DEFAULT 'outro',
  `cnpj` varchar(30) DEFAULT NULL,
  `telefone` varchar(30) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `observacao` text DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `fretes` (
  `id` int(11) NOT NULL,
  `tipo_lancamento` enum('resumido','detalhado') NOT NULL DEFAULT 'resumido',
  `id_veiculo` int(11) NOT NULL,
  `id_motorista` int(11) DEFAULT NULL,
  `id_operacao` int(11) DEFAULT NULL,
  `cliente` varchar(150) DEFAULT NULL,
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `data_frete` date DEFAULT NULL,
  `origem` varchar(150) DEFAULT NULL,
  `destino` varchar(150) DEFAULT NULL,
  `valor` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('lancado','faturado','recebido','cancelado') NOT NULL DEFAULT 'lancado',
  `observacao` text DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `manutencoes` (
  `id` int(11) NOT NULL,
  `id_veiculo` int(11) NOT NULL,
  `id_fornecedor` int(11) DEFAULT NULL,
  `id_operacao` int(11) DEFAULT NULL,
  `data_manutencao` date NOT NULL,
  `tipo_manutencao` enum('preventiva','corretiva') NOT NULL DEFAULT 'corretiva',
  `servico` varchar(150) NOT NULL,
  `descricao` text DEFAULT NULL,
  `valor_mao_obra` decimal(12,2) NOT NULL DEFAULT 0.00,
  `valor_pecas` decimal(12,2) NOT NULL DEFAULT 0.00,
  `valor_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `km_manutencao` decimal(12,2) DEFAULT NULL,
  `validade_km` decimal(12,2) DEFAULT NULL,
  `proxima_troca_km` decimal(12,2) DEFAULT NULL,
  `validade_dias` int(11) DEFAULT NULL,
  `proxima_troca_data` date DEFAULT NULL,
  `status` enum('aberta','andamento','concluida','cancelada') NOT NULL DEFAULT 'concluida',
  `observacao` text DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `motoristas` (
  `id` int(11) NOT NULL,
  `nome` varchar(120) NOT NULL,
  `cpf` varchar(20) DEFAULT NULL,
  `telefone` varchar(30) DEFAULT NULL,
  `cnh` varchar(50) DEFAULT NULL,
  `categoria_cnh` varchar(10) DEFAULT NULL,
  `tipo_vinculo` varchar(80) DEFAULT NULL,
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `observacao` text DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `operacoes` (
  `id` int(11) NOT NULL,
  `nome` varchar(120) NOT NULL,
  `cliente` varchar(120) DEFAULT NULL,
  `status` enum('ativa','inativa') NOT NULL DEFAULT 'ativa',
  `observacao` text DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `pagamentos` (
  `id` int(11) NOT NULL,
  `tipo_lancamento` enum('mensal','detalhado') NOT NULL DEFAULT 'mensal',
  `id_motorista` int(11) NOT NULL,
  `id_veiculo` int(11) DEFAULT NULL,
  `id_operacao` int(11) DEFAULT NULL,
  `mes_referencia` date DEFAULT NULL,
  `data_pagamento` date DEFAULT NULL,
  `tipo_pagamento` enum('salario','diaria','comissao','bonus','extra','ajuda_custo','adiantamento','desconto','outros') NOT NULL DEFAULT 'salario',
  `valor` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('lancado','pago','cancelado') NOT NULL DEFAULT 'lancado',
  `observacao` text DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(120) NOT NULL,
  `email` varchar(120) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nivel` enum('admin','usuario') NOT NULL DEFAULT 'usuario',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `veiculos` (
  `id` int(11) NOT NULL,
  `placa` varchar(20) NOT NULL,
  `marca` varchar(80) DEFAULT NULL,
  `modelo` varchar(100) NOT NULL,
  `ano` int(11) DEFAULT NULL,
  `tipo` varchar(80) DEFAULT NULL,
  `eixos` varchar(30) DEFAULT NULL,
  `km_atual` decimal(12,2) DEFAULT 0.00,
  `status` enum('ativo','manutencao','inativo') NOT NULL DEFAULT 'ativo',
  `observacao` text DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `abastecimentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_abast_veiculo_data` (`id_veiculo`, `data_abastecimento`, `km_atual`),
  ADD KEY `idx_abast_data` (`data_abastecimento`),
  ADD KEY `fk_abast_motorista` (`id_motorista`),
  ADD KEY `fk_abast_operacao` (`id_operacao`),
  ADD KEY `fk_abast_fornecedor` (`id_fornecedor`);

ALTER TABLE `fornecedores`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `fretes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_frete_veiculo` (`id_veiculo`),
  ADD KEY `fk_frete_motorista` (`id_motorista`),
  ADD KEY `fk_frete_operacao` (`id_operacao`);

ALTER TABLE `manutencoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_manut_veiculo` (`id_veiculo`),
  ADD KEY `fk_manut_fornecedor` (`id_fornecedor`),
  ADD KEY `fk_manut_operacao` (`id_operacao`);

ALTER TABLE `motoristas`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `operacoes`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `pagamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pag_motorista` (`id_motorista`),
  ADD KEY `fk_pag_veiculo` (`id_veiculo`),
  ADD KEY `fk_pag_operacao` (`id_operacao`);

ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

ALTER TABLE `veiculos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `placa` (`placa`);

ALTER TABLE `abastecimentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `fornecedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `fretes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `manutencoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `motoristas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `operacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `pagamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `veiculos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `abastecimentos`
  ADD CONSTRAINT `fk_abast_fornecedor` FOREIGN KEY (`id_fornecedor`) REFERENCES `fornecedores` (`id`),
  ADD CONSTRAINT `fk_abast_motorista` FOREIGN KEY (`id_motorista`) REFERENCES `motoristas` (`id`),
  ADD CONSTRAINT `fk_abast_operacao` FOREIGN KEY (`id_operacao`) REFERENCES `operacoes` (`id`),
  ADD CONSTRAINT `fk_abast_veiculo` FOREIGN KEY (`id_veiculo`) REFERENCES `veiculos` (`id`);

ALTER TABLE `fretes`
  ADD CONSTRAINT `fk_frete_motorista` FOREIGN KEY (`id_motorista`) REFERENCES `motoristas` (`id`),
  ADD CONSTRAINT `fk_frete_operacao` FOREIGN KEY (`id_operacao`) REFERENCES `operacoes` (`id`),
  ADD CONSTRAINT `fk_frete_veiculo` FOREIGN KEY (`id_veiculo`) REFERENCES `veiculos` (`id`);

ALTER TABLE `manutencoes`
  ADD CONSTRAINT `fk_manut_fornecedor` FOREIGN KEY (`id_fornecedor`) REFERENCES `fornecedores` (`id`),
  ADD CONSTRAINT `fk_manut_operacao` FOREIGN KEY (`id_operacao`) REFERENCES `operacoes` (`id`),
  ADD CONSTRAINT `fk_manut_veiculo` FOREIGN KEY (`id_veiculo`) REFERENCES `veiculos` (`id`);

ALTER TABLE `pagamentos`
  ADD CONSTRAINT `fk_pag_motorista` FOREIGN KEY (`id_motorista`) REFERENCES `motoristas` (`id`),
  ADD CONSTRAINT `fk_pag_operacao` FOREIGN KEY (`id_operacao`) REFERENCES `operacoes` (`id`),
  ADD CONSTRAINT `fk_pag_veiculo` FOREIGN KEY (`id_veiculo`) REFERENCES `veiculos` (`id`);

