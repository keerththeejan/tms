-- Update existing delivery note to have an amount
UPDATE delivery_notes SET total_amount = 15000.00 WHERE id = 1;

-- Create additional delivery notes with dues
INSERT INTO delivery_notes (customer_id, branch_id, delivery_date, total_amount) VALUES 
(1, 1, '2025-09-14', 8500.50),
(1, 1, '2025-09-15', 12000.00);

-- Verify the data
SELECT dn.*, c.name AS customer_name, 
       (dn.total_amount - COALESCE(paid.total_paid,0)) AS due,
       COALESCE(paid.total_paid,0) AS paid
FROM delivery_notes dn
LEFT JOIN customers c ON c.id = dn.customer_id
LEFT JOIN (
    SELECT delivery_note_id, SUM(amount) AS total_paid
    FROM payments GROUP BY delivery_note_id
) paid ON paid.delivery_note_id = dn.id
WHERE (dn.total_amount - COALESCE(paid.total_paid,0)) > 0.01;
