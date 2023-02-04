ALTER TABLE `contests_submissions` MODIFY `score` DECIMAL(15, 10) NOT NULL;
ALTER TABLE `submissions` MODIFY `score` DECIMAL(15, 10) DEFAULT NULL;
ALTER TABLE `submissions` MODIFY `hidden_score` DECIMAL(15, 10) DEFAULT NULL;
ALTER TABLE `submissions_history` MODIFY `score` DECIMAL(15, 10) DEFAULT NULL;
