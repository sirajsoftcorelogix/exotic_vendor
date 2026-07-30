-- Optional sidebar menu for Workflow Transition management.
-- Run manually after create_vp_workflow_transition.sql.
-- Assign permissions to roles via Admin → Roles if the item does not appear.

/*
INSERT INTO `modules` (`parent_id`, `module_name`, `slug`, `action`, `font_awesome_icon`, `active`, `user_id`, `sort_order`)
VALUES (0, 'Workflow Transitions', 'workflow_transition', 'list', '<i class="fas fa-project-diagram mr-2"></i>', 1, 1, 211);

SET @wf_parent := LAST_INSERT_ID();

INSERT INTO `modules` (`parent_id`, `module_name`, `slug`, `action`, `font_awesome_icon`, `active`, `user_id`, `sort_order`)
VALUES
(@wf_parent, 'Manage transitions', 'workflow_transition', 'list', '<i class="fas fa-list mr-2"></i>', 1, 1, 1);
*/
