-- Executar uma vez apenas em bancos da versão anterior.
ALTER TABLE abastecimentos
    MODIFY litros DECIMAL(11,3) NOT NULL,
    MODIFY arla_litros DECIMAL(11,3) NOT NULL DEFAULT 0,
    ADD COLUMN km_base_manual DECIMAL(12,2) NULL,
    ADD INDEX idx_abast_veiculo_data (id_veiculo, data_abastecimento, km_atual),
    ADD INDEX idx_abast_data (data_abastecimento);

-- Preserva bases históricas diferentes da leitura anterior da placa.
UPDATE abastecimentos a
JOIN (
    SELECT id, LAG(km_atual) OVER (
        PARTITION BY id_veiculo ORDER BY data_abastecimento, km_atual, id
    ) AS km_precedente
    FROM abastecimentos
) h ON h.id = a.id
SET a.km_base_manual = a.km_anterior
WHERE a.km_anterior IS NOT NULL
  AND (h.km_precedente IS NULL OR h.km_precedente <> a.km_anterior);
