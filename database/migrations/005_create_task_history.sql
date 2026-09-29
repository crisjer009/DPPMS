CREATE TABLE task_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    task_id BIGINT UNSIGNED NOT NULL,
    action_type VARCHAR(40) NOT NULL,
    field_name VARCHAR(64) NULL,
    old_value TEXT NULL,
    new_value TEXT NULL,
    changed_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_task_history_task_created (task_id, created_at),
    KEY idx_task_history_changed_by (changed_by),
    CONSTRAINT fk_task_history_task FOREIGN KEY (task_id) REFERENCES tasks (id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_task_history_changed_by FOREIGN KEY (changed_by) REFERENCES users (id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
