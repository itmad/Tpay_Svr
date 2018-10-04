/*
 Navicat Premium Data Transfer

 Source Server         : 搬瓦工
 Source Server Type    : MySQL
 Source Server Version : 50723

 Target Server Type    : MySQL
 Target Server Version : 50723
 File Encoding         : 65001

 Date: 02/10/2018 19:43:59
*/

SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for pay_black
-- ----------------------------
DROP TABLE IF EXISTS `pay_black`;
CREATE TABLE `pay_black`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` char(16) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '要加黑名单的IP值',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '黑名单列表，以IP为限制，防止部分人恶意攻击，暂时不加此功能' ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for pay_log
-- ----------------------------
DROP TABLE IF EXISTS `pay_log`;
CREATE TABLE `pay_log`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `content` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '日志内容主体',
  `ip` char(16) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '用户的ip值',
  `types` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '日志的类型编号，你自己定义1是什么日志，2是什么日志这类',
  `creattime` timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '本条日志的创建时间',
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '日志记录表，也没多少好说的。。' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for pay_police
-- ----------------------------
DROP TABLE IF EXISTS `pay_police`;
CREATE TABLE `pay_police`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `ip` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '对方ip值',
  `get_count` tinyint(3) UNSIGNED NOT NULL DEFAULT 1 COMMENT '获取二维码的次数，默认起始为1',
  `channel` char(8) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '支付渠道名 ，WECHAT或ALIPAY',
  `lasttime` timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '上次获取时间',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `ipindex`(`ip`, `channel`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 19 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '这个表每小时会清空一次' ROW_FORMAT = Fixed;

-- ----------------------------
-- Table structure for pay_task
-- ----------------------------
DROP TABLE IF EXISTS `pay_task`;
CREATE TABLE `pay_task`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `money` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '价格，单位为分',
  `url` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '二维码的字符串',
  `channel` char(8) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '支付渠道名,wechat或alipay',
  `task_extra` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '此订单的附加值，可传激活码和用户名等等，自己分割',
  `mark_sell` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '收款方备注',
  `mark_buy` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '付款方备注',
  `order_id` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '订单编号',
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0未支付，大于1为支付成功的次数',
  `apply_time` timestamp(0) NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '二维码的申请时间',
  `creat_time` timestamp(0) NULL DEFAULT NULL COMMENT '二维码创建成功的时间',
  `end_time` timestamp(0) NULL DEFAULT NULL COMMENT '支付完结时间',
  `apply_ip` char(16) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '申请此二维码的用户的ip地址，为了方便，弄成文本吧',
  `descp` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '此订单交易的描述信息',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `mianindex`(`mark_sell`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '支付任务的主要表' ROW_FORMAT = Dynamic;

-- ----------------------------
-- Table structure for test_tab_user
-- ----------------------------
DROP TABLE IF EXISTS `test_tab_user`;
CREATE TABLE `test_tab_user`  (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user` char(16) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '' COMMENT '用户名',
  `money` int(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '比如这是用户的金额，测试之后看是否金额有增加即可',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `user_index`(`user`) USING BTREE
) ENGINE = MyISAM AUTO_INCREMENT = 7 CHARACTER SET = utf8 COLLATE = utf8_general_ci COMMENT = '你们可以照葫芦画瓢用的测试表' ROW_FORMAT = Fixed;

-- ----------------------------
-- Event structure for delete_police
-- ----------------------------
DROP EVENT IF EXISTS `delete_police`;
delimiter ;;
CREATE DEFINER = `root`@`%` EVENT `delete_police`
ON SCHEDULE
EVERY '1' HOUR STARTS '2018-10-02 19:42:24'
ON COMPLETION PRESERVE
COMMENT 'auto_delete_police'
DO BEGIN
  delete from pay_police;
END
;;
delimiter ;

SET FOREIGN_KEY_CHECKS = 1;
