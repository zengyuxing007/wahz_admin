-- MySQL dump 10.11
--
-- Host: localhost    Database: wahz
-- ------------------------------------------------------
-- Server version	5.0.95

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_menu`
--

DROP TABLE IF EXISTS `admin_menu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_menu` (
  `id` int(10) unsigned NOT NULL auto_increment COMMENT '菜单id（自增）',
  `menu_name` varchar(255) NOT NULL default '' COMMENT '菜单名称',
  `add_name` varchar(255) NOT NULL default '' COMMENT '附加名称',
  `parent_id` int(10) unsigned NOT NULL default '0' COMMENT '父级id',
  `controller` varchar(30) NOT NULL default '' COMMENT '控制器',
  `action` varchar(30) NOT NULL default '' COMMENT '操作',
  `res_type` int(10) unsigned NOT NULL default '0' COMMENT '资源类型',
  `view_order` tinyint(3) unsigned NOT NULL default '0' COMMENT '查看顺序',
  `is_show` tinyint(3) unsigned NOT NULL COMMENT '是否显示',
  PRIMARY KEY  (`id`),
  KEY `view_order` (`view_order`)
) ENGINE=MyISAM AUTO_INCREMENT=89 DEFAULT CHARSET=utf8 COMMENT='后台菜单表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_menu`
--

LOCK TABLES `admin_menu` WRITE;
/*!40000 ALTER TABLE `admin_menu` DISABLE KEYS */;
INSERT INTO `admin_menu` VALUES (18,'菜单列表','',16,'menu','list',0,0,1),(16,'系统管理','',0,'','',0,4,1),(7,'用户管理','',0,'','',0,3,1),(8,'添加用户','添加',7,'user','add',0,0,1),(9,'用户列表','',7,'user','list',0,0,1),(10,'修改密码','',7,'user','user_settings',0,0,1),(17,'用户组列表','',16,'user_role','list',0,0,1),(52,'我爱汉字后台管理','',0,'','',0,0,1),(53,'发布新闻','',52,'add_news','view',0,0,1),(60,'用户查询','',52,'account','info',0,0,1),(61,'奖品基本配置','',52,'config_all','l',0,0,1),(62,'奖品发放管理','',52,'reward','list_used',0,0,1),(63,'IOS消息推送','',52,'push','index',0,0,1),(64,'已有新闻列表','',52,'news_info','news_list',0,0,1),(66,'节目安排管理','',52,'show','view',0,0,1),(67,'其他图片素材上传','',52,'upload','view',0,0,1),(65,'兑换码生成','',52,'make_card','makeCard',0,0,1);
/*!40000 ALTER TABLE `admin_menu` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_user`
--

DROP TABLE IF EXISTS `admin_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_user` (
  `id` int(10) unsigned NOT NULL auto_increment COMMENT '用户id',
  `user_name` varchar(255) NOT NULL default '' COMMENT '用户名',
  `password` varchar(255) NOT NULL default '' COMMENT '密码',
  `role_id` tinyint(3) unsigned NOT NULL default '0' COMMENT '用户组id',
  `avatar` varchar(255) NOT NULL default '' COMMENT '用户头像',
  `email` varchar(255) NOT NULL default '' COMMENT 'email',
  `mobile` varchar(255) NOT NULL default '' COMMENT '手机号',
  `hash` char(10) NOT NULL default '' COMMENT '随机验证串',
  `add_time` datetime NOT NULL COMMENT '注册时间',
  `update_time` timestamp NOT NULL default CURRENT_TIMESTAMP COMMENT '在线判断时间',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8 COMMENT='用户表';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_user`
--

LOCK TABLES `admin_user` WRITE;
/*!40000 ALTER TABLE `admin_user` DISABLE KEYS */;
INSERT INTO `admin_user` VALUES (11,'admin','14d24390dd9343edad142869a1aeee5c',1,'','','','','0000-00-00 00:00:00','2011-12-31 08:39:46');
/*!40000 ALTER TABLE `admin_user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_user_role`
--

DROP TABLE IF EXISTS `admin_user_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_user_role` (
  `id` tinyint(3) unsigned NOT NULL auto_increment COMMENT '用户组id',
  `name` varchar(255) NOT NULL default '' COMMENT '用户组名称',
  `type` tinyint(3) unsigned NOT NULL default '1' COMMENT '用户组类别',
  `privilege` text NOT NULL COMMENT 'delete_user,delete_grave',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COMMENT='用户组';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_user_role`
--

LOCK TABLES `admin_user_role` WRITE;
/*!40000 ALTER TABLE `admin_user_role` DISABLE KEYS */;
INSERT INTO `admin_user_role` VALUES (1,'超级管理员',1,'*'),(2,'普通管理员',1,'37,44,38,40,41,42,43,51,56,57,58,72,75,76,52,53,59,79,60,78,61,62,63,64,65,66,67,68,70,71,74,83,77,49,50,54,55,73'),(3,'高级客服',2,'37,38,60,78,61,62,63,64,65,66,67,68,70,71,74'),(4,'普通客服',2,'37,38,60,61,62,63,64,65,66,67,68,70,71');
/*!40000 ALTER TABLE `admin_user_role` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `advice`
--

DROP TABLE IF EXISTS `advice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `advice` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `unique_id` varchar(64) NOT NULL default '',
  `info` varchar(256) default NULL,
  `create_time` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `advice`
--

LOCK TABLES `advice` WRITE;
/*!40000 ALTER TABLE `advice` DISABLE KEYS */;
INSERT INTO `advice` VALUES (1,'53e9de49283d86fedcbb33d6e8cd35327a424ba5','最多100个字符',1394539945);
/*!40000 ALTER TABLE `advice` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `config_reward`
--

DROP TABLE IF EXISTS `config_reward`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `config_reward` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `name` varchar(32) NOT NULL default '',
  `reward_img_url` varchar(64) NOT NULL default '',
  `record_time` int(11) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `config_reward`
--

LOCK TABLES `config_reward` WRITE;
/*!40000 ALTER TABLE `config_reward` DISABLE KEYS */;
INSERT INTO `config_reward` VALUES (1,'活字印刷字模','http://app.setv.sh.cn/uploadimg/1394541881.png',1394543440);
/*!40000 ALTER TABLE `config_reward` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `device_info`
--

DROP TABLE IF EXISTS `device_info`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `device_info` (
  `unique_id` varchar(64) NOT NULL default '',
  `device_token` varchar(64) NOT NULL default '',
  `create_time` int(11) NOT NULL,
  PRIMARY KEY  (`unique_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `device_info`
--

LOCK TABLES `device_info` WRITE;
/*!40000 ALTER TABLE `device_info` DISABLE KEYS */;
INSERT INTO `device_info` VALUES ('0f92ff2440034a15fe1a54f6a841464fbcfb8913','d170a83c64cbf6e8344328e53dbedd1a4b5b0a17437d660eaa1d3e8d3e3be017',1394548305),('12211221221','12112',1394540737),('799277ed60759414cfc82574942b17dd2083ff85','2930fe9d12387746194ad5863c8e43f36ed2f3b18915f3805b47e0b08802b080',1394593706),('bbbc70b54018ffd9f8fd04688fcd08cad928773d','d170a83c64cbf6e8344328e53dbedd1a4b5b0a17437d660eaa1d3e8d3e3be017',1394597156),('f4ac1f17d59cffc91b24f321de71f01d7a7e45c5','910e551f937d31fb5ad4d861be55677cecad9e5ec4f50f1daffb4d502edbc02c',1394598322);
/*!40000 ALTER TABLE `device_info` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news_simple`
--

DROP TABLE IF EXISTS `news_simple`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `news_simple` (
  `id` int(11) unsigned NOT NULL auto_increment COMMENT 'id',
  `type` smallint(3) NOT NULL COMMENT '类型',
  `title` varchar(128) NOT NULL default '' COMMENT 'title',
  `content` text NOT NULL COMMENT '内容',
  `image_url` varchar(128) NOT NULL default '' COMMENT '图片url',
  `order` tinyint(3) NOT NULL default '0' COMMENT '排序',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news_simple`
--

LOCK TABLES `news_simple` WRITE;
/*!40000 ALTER TABLE `news_simple` DISABLE KEYS */;
INSERT INTO `news_simple` VALUES (4,1,'实验西校队挺进上海市“我爱汉字美”比赛12强','经过了初赛和笔试重重考验之后，实验西校队3月2日在上海教育电视台举办的“我爱汉字美”电视比赛中，成功晋级市12强。\r\n\r\n3月2日上午，我校八年级唐小丫、杨鹏飞、孙怡辰、韩卢露四位同学在指导老师鲍清、钱文萍老师的带领下，参加了第二届“中国汉字听写大会”上海赛区暨2014“我爱汉字美”咬文嚼字大赛选拔赛，同行的还有四位参赛选手的家长，以及25位啦啦队员。闵行区教育学院刘时医老师负责闵行区本项工作，她亲临观战。\r\n\r\n此次比赛是整场24强进12强的第四场，其他三个参赛学校为西南模范中学、曹阳中学、民办中芯学校。比赛分三个环节，第一、二环节过后，曹阳中学得分90分暂时领先，另三所学校同为60分，比赛气氛激烈而紧张。第三环节是最残酷的淘汰赛，上场选手只要答错一题即无答题资格。在第一环节中失利的杨鹏飞同学勇敢站在了台上，挑起了这份重担，他迅速调整心态，沉着答题。在第三环节的终极对抗中，曹阳中学、民办中芯学校相继出局，我校以80分的总分获得第二，与曹阳中学共同进入下周末的12强进8强的比赛。\r\n\r\n比赛中固然有机遇与巧合，但实力更重要。小选手们在第一、二轮比赛中虽然遭遇最难的题目（评委老师黄玉峰语）导致失去一些得分机会，但第三环节的比赛得以重展实力。\r\n\r\n四位选手和指导老师进行艰难的、全面的大赛准备已有三个多月，其间付出的艰辛可想而知。我们收获的不仅仅是比赛的成绩，更重要的是在准备中学习了许多文字文化知识，领略了祖国语言美。\r\n\r\n教育电视台“我爱汉字美”节目将在3月中旬后播出，希望节目引领更多的常态的汉字文化的学习。','1394536271.png',0,1394536293),(6,1,'上海中学生教育水平世界第一','　　英国BBC中文网日前发表文章，题为《经合组织：上海中学生教育水平世界第一》，文章摘编如下：经济合作与发展组织（OECD）的最新研究报告显示，上海中学生在数学、科学和阅读三方面的教育水平名列世界第一。\r\n　　这是上海首次单独参加OECD每三年进行一次的考评。\r\n　　周二（12月7日）公布的报告显示，按国家排名，韩国和芬兰在文化水平测试中仍然占据榜首。\r\n　　不过，单独参加考评的上海在上述三个领域都处于领先位置。\r\n　　文化程度排名第二的是香港、新加坡、加拿大、新西兰和日本。\r\n　　这个报告每三年公布一次，数据来自经合组织对较发达地区近70个国家的约50万名15岁学生的考核测验。考试时间为两个小时。\r\n　　OECD说，上海有25%以上的学生展示出运用复杂的数学思维解答难题的能力，而OECD成员国的平均水平是3%。\r\n　　但是，报告没有说明参加考试的学生来自什么学校。\r\n　　芬兰过去10年一直占据着PISA排行榜的榜首，这次仍然是欧洲国家里表现最好的。\r\n　　芬兰的综合表现和数学教育都排名第三，科学教育排名第二。\r\n　　韩国的综合表现排第二，数学排第四，科学排第六。\r\n　　参加调查的香港、台湾、新加坡和日本的表现都名列前茅。\r\n　　比利时、爱沙尼亚、冰岛、荷兰、挪威、波兰和瑞士这七个欧洲国家的成绩高于OECD平均水平。\r\n　　OECD指出，调查结果证明一个国家的教育水平跟它的国民收入水平仍有关系，但两个经济发达程度相似的国家教育成绩不一定相似。\r\n　　根据这次的调查，研究人员发现成绩好的学校更愿意给教师付高工资，而不是减少每个班级的学生人数。','1394542369.png',0,1394542496),(7,2,'和小伙伴们一起来玩趣味汉字闯关游戏吧','在过去的五千年，汉字成为上古时期世界各大文字体系中唯一幸存至今的表意文字，是在世界上影响最大的中国符号，是中国贡献于人类文明的第五大发明。 然而在下一个五千年，汉字是否还能如此幸运。 2014年，上海教育电视台将举办沪上首档语言文化类大型季播活动《我爱汉字美》，暨上海市中学生咬文嚼字活动。 不同于《汉字听写大会》等节目注重听和写或为单一的猜成语，《我爱汉字美》着重规范汉字使用，纠错能力比拼---由谬误学真知，由真知见机理，由机理识源流，由源流显文化。 你是否也想亲身参与此次意义非凡的活动呢？快来下载《我爱汉字美》APP版吧，通过有趣的汉字闯关游戏，轻松的增长汉字文化知识。','1394542577.png',0,1394542603),(8,2,'全家一起看《我爱汉字美》—上海教育电视台3月22日首播','在辨析中体会汉字的魅力，在生活中寻找汉字的乐趣。 \r\n    在过去的五千年，汉字成为上古时期世界各大文字体系中唯一幸存至今的表意文字，是在世界上影响最大的中国符号，是中国贡献于人类文明的第五大发明。 然而在下一个五千年，汉字是否还能如此幸运。 2014年，上海教育电视台将举办沪上首档语言文化类大型季播活动《我爱汉字美》，暨上海市中学生咬文嚼字活动。\r\n ——专业权威有文化品味，关注度不可小觑 \r\n   《我爱汉字美》由上海市语言文字工作委员会和上海市教育委员会主办，上海教育电视台和上海教育报刊总社少年报社承办的大型汉字规范使用、纠错比拼活动。活动由《咬文嚼字》杂志社进行题库提供，并全程专业支持。活动宣传中还将采访徐中玉、于漪、曹景行、陈村、丁建华、王丽萍等知名人物，同时韩寒微博、韩寒工作室将全程宣传。 节目形式独一无二，突出汉字的独特特魅力 不同于《汉字听写大会》等节目注重听和写或为单一的猜成语，《我爱汉字美》着重规范汉字使用，纠错能力比拼---由谬误学真知，由真知见机理，由机理识源流，由源流显文化。不同于时下千篇一律的娱乐综艺节目，不追求华丽的灯光舞美，不追求喧闹的现场效果，节目形态返璞归真，在这个春季教育台将奉上一道清新雅致的文化盛宴。 \r\n——知名主持人和资深点评嘉宾，看点迭出影响力广泛 \r\n    本次节目主持人为知名主持人---陈浩。他曾主持过江苏卫视大型选秀节目《绝对唱响》、《名师高徒》等，现任中国教育电视台全国大学生大型益智节目《天才知道》主持。 节目还将邀请学界最权威的语言学家和文化名人担任点评嘉宾。此次点评阵容由路金波、李蕾、黄玉峰三位文化名人组成，他们都是各自领域的佼佼者，届时必将碰撞出精彩的火花。 \r\n——竞争残酷夺人眼球，比赛环节扣人心弦 \r\n本次比赛设计了多个环节，将多角度立体呈现选手们对文字的综合理解运用能力。节目分轮答环节、选答环节和终极对决，同时题型丰富多彩，既有纠错写字题，也有图片题、朗读题、选择题更有终极对决时的判断对错题。全市各区、县将组成24支代表队参赛，形成紧张精彩的多场次晋级比赛框架，最后两三名选手的对决就像足球比赛中的点球大战，没有什么比这更激动人心了。《我爱汉字美》就将在这样令人窒息的残酷竞争中，给予每一个观众不断加速的心跳，令观众欲罢不能。\r\n    节目播出安排 \r\n    2014年3月22日至4月26日，上海教育电视台每周六、周日晚间黄金时段播出（拟定为20：25播出，时长70分钟，共计12场比赛，播出历时6周。） \r\n    具体赛程和播出安排如下： \r\n    2014年3月22日至4月6日，12强争夺战：24进12，共计6场； 2014年4月12日至4月19日，8强争夺战：12进8，共计3场； 2014年4月20日至4月25日，4强争夺战：8进4，共计2场；2014年4月26日，冠军争夺战，共计1场。','1394542652.png',0,1394542765),(9,2,'书上学不到的知识-盘点汉语中最常用却最常错的字词','在生活中在学习中你经常写错字吗？\r\n    如果有，也没神马不好意思的，这并不是多不给力的是，连一些权威媒体都会用错字词，最常用错的字词，看看你用错过没有： \r\n    一、世博报道中经常写错的成语是：美轮美奂。2010年上海世博会成功举办，园区中各国展馆千姿百态，“美轮美奂”便成了媒体描写这些展馆的常用词语，但常常错写成“美仑美奂”或“美伦美奂”。这一成语形容的是建筑物的高大美观，其中的“轮”含义为“高大”，写成“仑”或“伦”，都是别字。 \r\n    二、世博报道中经常写错的地名是：黄浦江。上海世博会沿黄浦江布局，“黄浦江”因此成为世博报道中的高频词语，但因为“黄浦”和“黄埔”音同形近，往往错成了“黄埔江”。“黄浦江”相传和战国春申君黄歇有关，故名“黄歇浦”，简称“黄浦”，“浦”义为水滨或水流交汇处。“黄埔”位于广东省广州市，因历史上成立过黄埔军校而闻名。 \r\n    三、统计数量时经常混淆的词是：截止/截至。“截止下午5点，入园参观人数已超过30万。”其中“截止”应为“截至”。“截止”的意思是停止，一般用于某一时间之后，如“活动已于昨日截止”；而用于某一时间之前的应当是“截至”，如“截至昨日，已有上千人报名”。\r\n        四、新闻报道中容易用错的词是：侧目。如：“他的研究成果解决了十多亿人的吃饭问题，令世界为之侧目。”“这位小将在广州亚运会上的成绩离世界纪录只有1秒，令人侧目。”这里的“侧目”应改为“瞩目”之类的词语。所谓“侧目”，是指斜目而视，形容愤恨或者畏惧的样子，它和“瞩目”完全是两回事。 \r\n    五、体育报道中经常用错的词是：囊括。广州亚运会报道中曾有这样的句子：“中国军团在2010年广州亚运会囊括金牌199枚，位居金牌榜首位。”“宁波选手广州亚运囊括10金。”其中的“囊括”明显用词不当。“囊括”的意思是无一遗漏，只要不是将所有的金牌都收入囊中，就不能用“囊括”。 \r\n    六、繁体字容易误认的是：晝。“晝”是“昼”的繁体字，常被误认作“書”（书）或“畫”（画）。2010年中央电视台元宵晚会便把古诗名句“花市灯如昼”误读为“花市灯如书”。选入某教材的古文名篇《昼锦堂记》，也被误作《画锦堂记》。这都是因为认错了繁体字“晝”。','1394542803.png',0,1394542912);
/*!40000 ALTER TABLE `news_simple` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `show_list`
--

DROP TABLE IF EXISTS `show_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `show_list` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `show_time` int(11) NOT NULL COMMENT '节目时间',
  `desc` varchar(64) NOT NULL default '' COMMENT '节目描述',
  `create_time` int(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `show_list`
--

LOCK TABLES `show_list` WRITE;
/*!40000 ALTER TABLE `show_list` DISABLE KEYS */;
INSERT INTO `show_list` VALUES (1,1395421200,'首播 24进12 第1场',0),(2,1395507600,'24进12 第2场',0),(3,1396026000,' 24进12 第3场',0),(4,1396112400,'24进12 第4场',0);
/*!40000 ALTER TABLE `show_list` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_data`
--

DROP TABLE IF EXISTS `user_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_data` (
  `unique_id` varchar(64) NOT NULL default '',
  `device_type` tinyint(3) NOT NULL COMMENT '设备类型',
  `user_name` varchar(32) NOT NULL default '' COMMENT '昵称',
  `phone_no` varchar(16) NOT NULL default '''''' COMMENT '手机号码',
  `address` varchar(64) NOT NULL default '''''' COMMENT '地址',
  `zcode` varchar(11) NOT NULL default '' COMMENT '邮编',
  `update_time` int(11) NOT NULL COMMENT '更新时间',
  PRIMARY KEY  (`unique_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_data`
--

LOCK TABLES `user_data` WRITE;
/*!40000 ALTER TABLE `user_data` DISABLE KEYS */;
INSERT INTO `user_data` VALUES ('0f92ff2440034a15fe1a54f6a841464fbcfb8913',1,'lesa','13918157127','3@sjdjdbdnsns','20000',1394548256),('356534052642426',2,'','','','',1394551628),('7778cbdd3668e33ac2db743c251de57292b7cdc6',1,'dd','dd','Dd','dd',1394550055),('799277ed60759414cfc82574942b17dd2083ff85',1,'lesa','13917157127','hh','gh',1394596313),('861135021389624',2,'家居','555','简历','888',1394550243),('863472021471351',2,'','','','',1394550282),('bbbc70b54018ffd9f8fd04688fcd08cad928773d',1,'dbebe','ggh','hhhh','39282@',1394561292);
/*!40000 ALTER TABLE `user_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wahz_card`
--

DROP TABLE IF EXISTS `wahz_card`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wahz_card` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `reward_type` tinyint(3) NOT NULL COMMENT '奖励类型',
  `code` varchar(11) NOT NULL default '' COMMENT '兑换码',
  `is_use` tinyint(3) NOT NULL COMMENT '是否使用',
  `uid` varchar(64) NOT NULL default '',
  `getTime` int(11) NOT NULL,
  `makeTime` int(11) NOT NULL,
  `activeTime` int(11) NOT NULL,
  `invalidTime` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=201 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wahz_card`
--

LOCK TABLES `wahz_card` WRITE;
/*!40000 ALTER TABLE `wahz_card` DISABLE KEYS */;
INSERT INTO `wahz_card` VALUES (1,1,'VTA4NOPV',2,'12112',0,20140311,1394540919,1399651200),(2,1,'POWITP3K',1,'0f92ff2440034a15fe1a54f6a841464fbcfb8913',0,20140311,1394545017,1399651200),(3,1,'VM5GGWBM',2,'861135021389624',0,20140311,1394545054,1399651200),(4,1,'XBYTZADB',2,'861135021389624',0,20140311,1394545182,1399651200),(5,1,'9CCA44M6',2,'7778cbdd3668e33ac2db743c251de57292b7cdc6',0,20140311,1394545234,1399651200),(6,1,'55DHGHAD',2,'861135021389624',0,20140311,1394545288,1399651200),(7,1,'1TWCTIB0',2,'7778cbdd3668e33ac2db743c251de57292b7cdc6',0,20140311,1394545324,1399651200),(8,1,'77VSKRGA',2,'7778cbdd3668e33ac2db743c251de57292b7cdc6',0,20140311,1394545374,1399651200),(9,1,'5F6048M5',2,'861135021389624',0,20140311,1394545400,1399651200),(10,1,'L2LWL1J1',2,'7778cbdd3668e33ac2db743c251de57292b7cdc6',0,20140311,1394545599,1399651200),(11,1,'8PYUTW8G',2,'7778cbdd3668e33ac2db743c251de57292b7cdc6',0,20140311,1394545798,1399651200),(12,1,'495W66IV',1,'7778cbdd3668e33ac2db743c251de57292b7cdc6',0,20140311,1394545980,1399651200),(13,1,'HEHXHX4P',1,'7778cbdd3668e33ac2db743c251de57292b7cdc6',0,20140311,1394546330,1399651200),(14,1,'JJ48QEVM',1,'7778cbdd3668e33ac2db743c251de57292b7cdc6',0,20140311,1394546510,1399651200),(15,1,'MXCFMO1Q',1,'861135021389624',0,20140311,1394546744,1399651200),(16,1,'ZQVO900L',2,'7778cbdd3668e33ac2db743c251de57292b7cdc6',0,20140311,1394546796,1399651200),(17,1,'7D67HJ7I',2,'7778cbdd3668e33ac2db743c251de57292b7cdc6',0,20140311,1394547090,1399651200),(18,1,'A2K27D65',2,'7778cbdd3668e33ac2db743c251de57292b7cdc6',0,20140311,1394547170,1399651200),(19,1,'EPLBICCF',2,'356534052642426',0,20140311,1394547176,1399651200),(20,1,'XB0B3VIV',2,'356534052642426',0,20140311,1394547269,1399651200),(21,1,'2LSB4LQ0',2,'7778cbdd3668e33ac2db743c251de57292b7cdc6',0,20140311,1394547327,1399651200),(22,1,'PD46P553',2,'356534052642426',0,20140311,1394547353,1399651200),(23,1,'Z6CPTN9P',2,'356534052642426',0,20140311,1394547384,1399651200),(24,1,'PA1W0Q5P',2,'861135021389624',0,20140311,1394547515,1399651200),(25,1,'ADZ6QQFQ',2,'861135021389624',0,20140311,1394547552,1399651200),(26,1,'VKTQKR7X',2,'861135021389624',0,20140311,1394547595,1399651200),(27,1,'VFSVQGWY',1,'7778cbdd3668e33ac2db743c251de57292b7cdc6',0,20140311,1394547664,1399651200),(28,1,'X3KXFCF3',1,'7778cbdd3668e33ac2db743c251de57292b7cdc6',0,20140311,1394547698,1399651200),(29,1,'R285IIRW',1,'7778cbdd3668e33ac2db743c251de57292b7cdc6',0,20140311,1394547732,1399651200),(30,1,'L0UO6OZL',1,'7778cbdd3668e33ac2db743c251de57292b7cdc6',0,20140311,1394547760,1399651200),(31,1,'11FVDDIV',1,'7778cbdd3668e33ac2db743c251de57292b7cdc6',0,20140311,1394547797,1399651200),(32,1,'DYNZ2OJP',2,'7778cbdd3668e33ac2db743c251de57292b7cdc6',0,20140311,1394547857,1399651200),(33,1,'3KSA8K6K',1,'7778cbdd3668e33ac2db743c251de57292b7cdc6',0,20140311,1394547905,1399651200),(34,1,'TKF3KOKU',1,'7778cbdd3668e33ac2db743c251de57292b7cdc6',0,20140311,1394547930,1399651200),(35,1,'W2EDIQPH',1,'7778cbdd3668e33ac2db743c251de57292b7cdc6',0,20140311,1394548154,1399651200),(36,1,'LKYVVK8J',1,'0f92ff2440034a15fe1a54f6a841464fbcfb8913',0,20140311,1394548262,1399651200),(37,1,'929K2K9W',1,'7778cbdd3668e33ac2db743c251de57292b7cdc6',0,20140311,1394548614,1399651200),(38,1,'XT7VEYYF',1,'7778cbdd3668e33ac2db743c251de57292b7cdc6',0,20140311,1394549264,1399651200),(39,1,'B9UIO829',1,'7778cbdd3668e33ac2db743c251de57292b7cdc6',0,20140311,1394549289,1399651200),(40,1,'X3H7P4HL',1,'7778cbdd3668e33ac2db743c251de57292b7cdc6',0,20140311,1394549351,1399651200),(41,1,'LMS8TSET',1,'861135021389624',0,20140311,1394550230,1399651200),(42,1,'TD33L5LB',1,'863472021471351',0,20140311,1394550238,1399651200),(43,1,'S8HO1RI2',2,'861135021389624',0,20140311,1394550244,1399651200),(44,1,'8VJ3D4HN',2,'863472021471351',0,20140311,1394550282,1399651200),(45,1,'7DAJZFAB',1,'12112',0,20140311,1394550986,1399651200),(46,1,'N3RR2YKG',1,'356534052642426',0,20140311,1394551569,1399651200),(47,1,'SGKMOL32',1,'356534052642426',0,20140311,1394551628,1399651200),(48,1,'K1M1SWEG',0,'',0,20140311,0,1399651200),(49,1,'DI4S4BXZ',0,'',0,20140311,0,1399651200),(50,1,'4GX1M9X1',0,'',0,20140311,0,1399651200),(51,1,'K7MLFFF5',0,'',0,20140311,0,1399651200),(52,1,'EZ0B430B',0,'',0,20140311,0,1399651200),(53,1,'Q0Z6R3V5',0,'',0,20140311,0,1399651200),(54,1,'WUVGZ3AS',0,'',0,20140311,0,1399651200),(55,1,'15JM4GLC',0,'',0,20140311,0,1399651200),(56,1,'RDZTLF20',0,'',0,20140311,0,1399651200),(57,1,'NLGM2QAI',0,'',0,20140311,0,1399651200),(58,1,'P5CM5G4N',0,'',0,20140311,0,1399651200),(59,1,'FDYNLMQ7',0,'',0,20140311,0,1399651200),(60,1,'AY3N6A6N',0,'',0,20140311,0,1399651200),(61,1,'OORBWBPQ',0,'',0,20140311,0,1399651200),(62,1,'9BJYZZ7Z',0,'',0,20140311,0,1399651200),(63,1,'JTW69V36',0,'',0,20140311,0,1399651200),(64,1,'9WUOB2W7',0,'',0,20140311,0,1399651200),(65,1,'KAQKDELA',0,'',0,20140311,0,1399651200),(66,1,'YQ1U0U5Y',0,'',0,20140311,0,1399651200),(67,1,'MXTDEDNG',0,'',0,20140311,0,1399651200),(68,1,'9TV3ZHB9',0,'',0,20140311,0,1399651200),(69,1,'K1CK1FCG',0,'',0,20140311,0,1399651200),(70,1,'PEPDLCCB',0,'',0,20140311,0,1399651200),(71,1,'BY888J0K',0,'',0,20140311,0,1399651200),(72,1,'YLSJ4I4U',0,'',0,20140311,0,1399651200),(73,1,'CA6YKYA9',0,'',0,20140311,0,1399651200),(74,1,'ZLHOEBOS',0,'',0,20140311,0,1399651200),(75,1,'5HBDKD4G',0,'',0,20140311,0,1399651200),(76,1,'OBJLV5T5',0,'',0,20140311,0,1399651200),(77,1,'BJR7FJOM',0,'',0,20140311,0,1399651200),(78,1,'OO4OV9ZZ',0,'',0,20140311,0,1399651200),(79,1,'7QCQPD0H',0,'',0,20140311,0,1399651200),(80,1,'MGY9Y07Z',0,'',0,20140311,0,1399651200),(81,1,'CWRAPCCA',0,'',0,20140311,0,1399651200),(82,1,'KG77CXKK',0,'',0,20140311,0,1399651200),(83,1,'MDTDG3VY',0,'',0,20140311,0,1399651200),(84,1,'7YIT0YTY',0,'',0,20140311,0,1399651200),(85,1,'EPTPUM0C',0,'',0,20140311,0,1399651200),(86,1,'ZBCIR7L7',0,'',0,20140311,0,1399651200),(87,1,'ZW4V5U4A',0,'',0,20140311,0,1399651200),(88,1,'9S97ZDG2',0,'',0,20140311,0,1399651200),(89,1,'GM9K5J5M',0,'',0,20140311,0,1399651200),(90,1,'77NIAR5A',0,'',0,20140311,0,1399651200),(91,1,'W4O3YKKZ',0,'',0,20140311,0,1399651200),(92,1,'8L8LMMMU',0,'',0,20140311,0,1399651200),(93,1,'7B11GWSS',0,'',0,20140311,0,1399651200),(94,1,'2BO54D4X',0,'',0,20140311,0,1399651200),(95,1,'S61XDDNK',0,'',0,20140311,0,1399651200),(96,1,'5Z0JYXCG',0,'',0,20140311,0,1399651200),(97,1,'TXWE6VGJ',0,'',0,20140311,0,1399651200),(98,1,'YOVX1YVX',0,'',0,20140311,0,1399651200),(99,1,'VOUXSSOM',0,'',0,20140311,0,1399651200),(100,1,'2X061VFK',0,'',0,20140311,0,1399651200),(101,1,'2POOIZ3M',0,'',0,20140311,0,1399651200),(102,1,'VFOHHVZR',0,'',0,20140311,0,1399651200),(103,1,'VS43S9PT',0,'',0,20140311,0,1399651200),(104,1,'XMXUX7UM',0,'',0,20140311,0,1399651200),(105,1,'CII24QTX',0,'',0,20140311,0,1399651200),(106,1,'6QQ6J826',0,'',0,20140311,0,1399651200),(107,1,'EQQDTVW2',0,'',0,20140311,0,1399651200),(108,1,'E9QZ9NIN',0,'',0,20140311,0,1399651200),(109,1,'I7H4C556',0,'',0,20140311,0,1399651200),(110,1,'PYVGCFZU',0,'',0,20140311,0,1399651200),(111,1,'4JRFR38N',0,'',0,20140311,0,1399651200),(112,1,'VSSJ0YHF',0,'',0,20140311,0,1399651200),(113,1,'YY3UUPJF',0,'',0,20140311,0,1399651200),(114,1,'XXJ4JMRY',0,'',0,20140311,0,1399651200),(115,1,'FBIQK2FF',0,'',0,20140311,0,1399651200),(116,1,'177EHILL',0,'',0,20140311,0,1399651200),(117,1,'RMC710B2',0,'',0,20140311,0,1399651200),(118,1,'O6FFBIOF',0,'',0,20140311,0,1399651200),(119,1,'8S1OX17H',0,'',0,20140311,0,1399651200),(120,1,'0AA7F713',0,'',0,20140311,0,1399651200),(121,1,'HYUDEY0R',0,'',0,20140311,0,1399651200),(122,1,'EK92GTDP',0,'',0,20140311,0,1399651200),(123,1,'QLFTR6FT',0,'',0,20140311,0,1399651200),(124,1,'84E8ILZ9',0,'',0,20140311,0,1399651200),(125,1,'NQNC3C92',0,'',0,20140311,0,1399651200),(126,1,'RQZRRFM6',0,'',0,20140311,0,1399651200),(127,1,'I46AZAX6',0,'',0,20140311,0,1399651200),(128,1,'6SIDBS7J',0,'',0,20140311,0,1399651200),(129,1,'GEKDDKG9',0,'',0,20140311,0,1399651200),(130,1,'I9KHQGEL',0,'',0,20140311,0,1399651200),(131,1,'R96AY4YY',0,'',0,20140311,0,1399651200),(132,1,'AJ0SQ800',0,'',0,20140311,0,1399651200),(133,1,'4DJJB4Z4',0,'',0,20140311,0,1399651200),(134,1,'2EJGF0SC',0,'',0,20140311,0,1399651200),(135,1,'B2CDD6FD',0,'',0,20140311,0,1399651200),(136,1,'S77M7I2E',0,'',0,20140311,0,1399651200),(137,1,'OR3LLHTJ',0,'',0,20140311,0,1399651200),(138,1,'TQZEI9DI',0,'',0,20140311,0,1399651200),(139,1,'X7ER1S4S',0,'',0,20140311,0,1399651200),(140,1,'B4NHWMMS',0,'',0,20140311,0,1399651200),(141,1,'WP7YFBH4',0,'',0,20140311,0,1399651200),(142,1,'3XX9QVBX',0,'',0,20140311,0,1399651200),(143,1,'N6TNTJID',0,'',0,20140311,0,1399651200),(144,1,'WUHWS8H8',0,'',0,20140311,0,1399651200),(145,1,'EN2WF7NA',0,'',0,20140311,0,1399651200),(146,1,'54J9900J',0,'',0,20140311,0,1399651200),(147,1,'BIQWBWSO',0,'',0,20140311,0,1399651200),(148,1,'POOIZYQV',0,'',0,20140311,0,1399651200),(149,1,'4CZW9CYE',0,'',0,20140311,0,1399651200),(150,1,'3WB1D738',0,'',0,20140311,0,1399651200),(151,1,'BEXVUDH6',0,'',0,20140311,0,1399651200),(152,1,'QCR9334I',0,'',0,20140311,0,1399651200),(153,1,'X2O97DBM',0,'',0,20140311,0,1399651200),(154,1,'42JM1XZX',0,'',0,20140311,0,1399651200),(155,1,'QRBJ6GQ5',0,'',0,20140311,0,1399651200),(156,1,'0YSPQZHS',0,'',0,20140311,0,1399651200),(157,1,'KRRE8ZOV',0,'',0,20140311,0,1399651200),(158,1,'XJ90CS6Z',0,'',0,20140311,0,1399651200),(159,1,'C1T01LTD',0,'',0,20140311,0,1399651200),(160,1,'WTWAK2PQ',0,'',0,20140311,0,1399651200),(161,1,'MMVRRVRR',0,'',0,20140311,0,1399651200),(162,1,'MIIAEJHM',0,'',0,20140311,0,1399651200),(163,1,'W6XQRV37',0,'',0,20140311,0,1399651200),(164,1,'HNIIEETN',0,'',0,20140311,0,1399651200),(165,1,'J5TCEP18',0,'',0,20140311,0,1399651200),(166,1,'1D6XUDD3',0,'',0,20140311,0,1399651200),(167,1,'QQCZCHCF',0,'',0,20140311,0,1399651200),(168,1,'6R8X12R0',0,'',0,20140311,0,1399651200),(169,1,'NYXBZS4X',0,'',0,20140311,0,1399651200),(170,1,'WHXO88BW',0,'',0,20140311,0,1399651200),(171,1,'LIJLULI8',0,'',0,20140311,0,1399651200),(172,1,'GUM6G0BB',0,'',0,20140311,0,1399651200),(173,1,'FLE8KEDR',0,'',0,20140311,0,1399651200),(174,1,'4F08ZD64',0,'',0,20140311,0,1399651200),(175,1,'IQVUZZRZ',0,'',0,20140311,0,1399651200),(176,1,'VWQDJYH4',0,'',0,20140311,0,1399651200),(177,1,'VRS7DSEH',0,'',0,20140311,0,1399651200),(178,1,'E9RSPDSV',0,'',0,20140311,0,1399651200),(179,1,'W6J5CCW4',0,'',0,20140311,0,1399651200),(180,1,'F7ZB92B9',0,'',0,20140311,0,1399651200),(181,1,'1B16PI31',0,'',0,20140311,0,1399651200),(182,1,'U3Z2RCUR',0,'',0,20140311,0,1399651200),(183,1,'DFW2JF1A',0,'',0,20140311,0,1399651200),(184,1,'DGVEU5JD',0,'',0,20140311,0,1399651200),(185,1,'JOTGGG71',0,'',0,20140311,0,1399651200),(186,1,'TOZKFOT0',0,'',0,20140311,0,1399651200),(187,1,'AO0R8EE4',0,'',0,20140311,0,1399651200),(188,1,'EOGKKGK0',0,'',0,20140311,0,1399651200),(189,1,'ZCNV2END',0,'',0,20140311,0,1399651200),(190,1,'ZWYZYYGW',0,'',0,20140311,0,1399651200),(191,1,'SJ7MXAIQ',0,'',0,20140311,0,1399651200),(192,1,'8DMILYT5',0,'',0,20140311,0,1399651200),(193,1,'7BUVVN9Z',0,'',0,20140311,0,1399651200),(194,1,'1JI12RUB',0,'',0,20140311,0,1399651200),(195,1,'0E0044O1',0,'',0,20140311,0,1399651200),(196,1,'OPXRIPA2',0,'',0,20140311,0,1399651200),(197,1,'W39P9MTC',0,'',0,20140311,0,1399651200),(198,1,'KOG3BGMH',0,'',0,20140311,0,1399651200),(199,1,'B0CBMMLB',0,'',0,20140311,0,1399651200),(200,1,'ADCHQOWR',0,'',0,20140311,0,1399651200);
/*!40000 ALTER TABLE `wahz_card` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2014-03-12 15:16:39
