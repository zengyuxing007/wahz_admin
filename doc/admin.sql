/*
Navicat MySQL Data Transfer

Source Server         : localhost
Source Server Version : 50128
Source Host           : localhost:3306
Source Database       : 61ant

Target Server Type    : MYSQL
Target Server Version : 50128
File Encoding         : 65001

Date: 2011-11-23 10:27:53
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for `admin_menu`
-- ----------------------------
DROP TABLE IF EXISTS `admin_menu`;
CREATE TABLE `admin_menu` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '菜单id（自增）',
  `menu_name` varchar(255) NOT NULL DEFAULT '' COMMENT '菜单名称',
  `add_name` varchar(255) NOT NULL DEFAULT '' COMMENT '附加名称',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '父级id',
  `controller` varchar(30) NOT NULL DEFAULT '' COMMENT '控制器',
  `action` varchar(30) NOT NULL DEFAULT '' COMMENT '操作',
  `res_type` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '资源类型',
  `view_order` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '查看顺序',
  PRIMARY KEY (`id`),
  KEY `view_order` (`view_order`)
) ENGINE=MyISAM AUTO_INCREMENT=46 DEFAULT CHARSET=utf8 COMMENT='后台菜单表';

-- ----------------------------
-- Records of admin_menu
-- ----------------------------
INSERT INTO `admin_menu` VALUES ('18', '菜单列表', '', '16', 'menu', 'list', '0', '0');
INSERT INTO `admin_menu` VALUES ('16', '系统管理', '', '0', '', '', '0', '4');
INSERT INTO `admin_menu` VALUES ('7', '用户管理', '', '0', '', '', '0', '3');
INSERT INTO `admin_menu` VALUES ('8', '添加用户', '添加', '7', 'user', 'add', '0', '0');
INSERT INTO `admin_menu` VALUES ('9', '用户列表', '', '7', 'user', 'list', '0', '0');
INSERT INTO `admin_menu` VALUES ('10', '修改密码', '', '7', 'user', 'user_settings', '0', '0');
INSERT INTO `admin_menu` VALUES ('17', '用户组列表', '', '16', 'user_role', 'list', '0', '0');
INSERT INTO `admin_menu` VALUES ('36', 'demo_liine', '', '35', 'highcharts_demo', 'line', '0', '0');
INSERT INTO `admin_menu` VALUES ('35', '图标demo', '', '0', '', '', '0', '5');
INSERT INTO `admin_menu` VALUES ('44', '在线人数统计（历史）', '', '37', 'stat', 'last_online', '0', '0');
INSERT INTO `admin_menu` VALUES ('37', '统计', '', '0', '', '', '0', '1');
INSERT INTO `admin_menu` VALUES ('38', '在线人数统计（当天）', '', '37', 'stat', 'online', '0', '0');
INSERT INTO `admin_menu` VALUES ('40', '活跃统计', '', '37', 'stat', 'active', '0', '0');
INSERT INTO `admin_menu` VALUES ('41', '登录统计', '', '37', 'stat', 'login', '0', '0');
INSERT INTO `admin_menu` VALUES ('42', '注册统计', '', '37', 'stat', 'register', '0', '0');
INSERT INTO `admin_menu` VALUES ('43', '流失统计', '', '37', 'stat', 'lost', '0', '0');

-- ----------------------------
-- Table structure for `admin_user`
-- ----------------------------
DROP TABLE IF EXISTS `admin_user`;
CREATE TABLE `admin_user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户id',
  `user_name` varchar(255) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '密码',
  `role_id` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '用户组id',
  `avatar` varchar(255) NOT NULL DEFAULT '' COMMENT '用户头像',
  `email` varchar(255) NOT NULL DEFAULT '' COMMENT 'email',
  `mobile` varchar(255) NOT NULL DEFAULT '' COMMENT '手机号',
  `hash` char(10) NOT NULL DEFAULT '' COMMENT '随机验证串',
  `add_time` datetime NOT NULL COMMENT '注册时间',
  `update_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '在线判断时间',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8 COMMENT='用户表';

-- ----------------------------
-- Records of admin_user
-- ----------------------------
INSERT INTO `admin_user` VALUES ('1', 'admin', 'e10adc3949ba59abbe56e057f20f883e', '1', '', '', '323', '', '0000-00-00 00:00:00', '2011-06-08 23:56:37');
INSERT INTO `admin_user` VALUES ('7', 'test_slave', 'e10adc3949ba59abbe56e057f20f883e', '2', '', '', '', '964bjyo8k', '0000-00-00 00:00:00', '2011-11-13 15:54:47');
INSERT INTO `admin_user` VALUES ('8', 'test_slave', 'e10adc3949ba59abbe56e057f20f883e', '2', '', '', '', 'o6yz9izzb6', '0000-00-00 00:00:00', '2011-11-13 15:56:51');
INSERT INTO `admin_user` VALUES ('9', 'test_master', 'e10adc3949ba59abbe56e057f20f883e', '2', '', '', '', 'sb49oyy46e', '0000-00-00 00:00:00', '2011-11-13 15:57:31');

-- ----------------------------
-- Table structure for `admin_user_role`
-- ----------------------------
DROP TABLE IF EXISTS `admin_user_role`;
CREATE TABLE `admin_user_role` (
  `id` tinyint(3) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户组id',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '用户组名称',
  `type` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '用户组类别',
  `privilege` text NOT NULL COMMENT 'delete_user,delete_grave',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8 COMMENT='用户组';

-- ----------------------------
-- Records of admin_user_role
-- ----------------------------
INSERT INTO `admin_user_role` VALUES ('1', 'test组', '1', '*');
INSERT INTO `admin_user_role` VALUES ('2', '测试二组', '1', '7,8,9,10,11,12');
INSERT INTO `admin_user_role` VALUES ('3', '测试一组', '3', '2,3');
