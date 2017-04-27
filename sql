# mysql root実行
create database loto;

# ユーザー作成(権限付与)
GRANT CREATE, CREATE VIEW, SHOW VIEW, INDEX, SELECT, INSERT, UPDATE, DELETE ON loto.* TO u_loto@localhost IDENTIFIED BY 'aaaa';
GRANT ALL PRIVILEGES ON loto.* TO u_loto@'localhost' IDENTIFIED BY 'aaaaa' WITH GRANT OPTION;

# table 作成
-- auto-generated definition
create table M_LOTO_TYPE
(
	ID int(2) not null
		primary key,
	LOTO_TYPE_NAME varchar(30) null,
	UPDATE_DATE datetime default CURRENT_TIMESTAMP null,
	INSERT_DATE datetime default CURRENT_TIMESTAMP null,
	ACTIVE_FLAG varchar(1) default 'Y' null
)ENGINE=InnoDB DEFAULT CHARSET=utf8
;

###

-- auto-generated definition
create table T_EVENT_NUMBER
(
	ID int(7) not null auto_increment
		primary key,
	EVENT_NUMBER int(7) null,
	LOTO_DATE date null,
	LOTO_TYPE_ID int(2) null,
	UPDATE_DATE datetime default CURRENT_TIMESTAMP null,
	INSERT_DATE datetime default CURRENT_TIMESTAMP null,
	ACTIVE_FLAG varchar(1) default 'Y' null,
	constraint T_EVENT_NUMBER_M_LOTO_TYPE_ID_fk
		foreign key (LOTO_TYPE_ID) references loto.M_LOTO_TYPE (ID)
)
;

create index T_EVENT_NUMBER_M_LOTO_TYPE_ID_fk
	on T_EVENT_NUMBER (LOTO_TYPE_ID)
;

###

-- auto-generated definition
create table T_LOTTERY_RESULT
(
	ID int(7) not null auto_increment
		primary key,
	EVENT_NUMBER_ID int(7) null,
	LOTO_NUBERS varchar(30) null,
	BONUS_NUBERS varchar(15) null,
	UPDATE_DATE datetime default CURRENT_TIMESTAMP null,
	INSERT_DATE datetime default CURRENT_TIMESTAMP null,
	ACTIVE_FLAG varchar(1) default 'Y' null,
	constraint T_LOTTERY_RESULT_T_EVENT_NUMBER_ID_fk
		foreign key (EVENT_NUMBER_ID) references loto.T_EVENT_NUMBER (ID)
)
;

create index T_LOTTERY_RESULT_T_EVENT_NUMBER_ID_fk
	on T_LOTTERY_RESULT (EVENT_NUMBER_ID)
;

# view 作成
CREATE VIEW V_LATEST_EVENT AS
  SELECT
    `l1`.`LOTO_TYPE_ID` AS `LOTO_TYPE_ID`,
    `l1`.`LOTO_DATE`    AS `LOTO_DATE`,
    `l1`.`EVENT_NUMBER` AS `EVENT_NUMBER`
  FROM (`loto`.`T_EVENT_NUMBER` `l1`
    JOIN (SELECT
            `loto`.`T_EVENT_NUMBER`.`LOTO_TYPE_ID`      AS `LOTO_TYPE_ID`,
            max(`loto`.`T_EVENT_NUMBER`.`EVENT_NUMBER`) AS `MAX_EVENT_NUMBER`
          FROM `loto`.`T_EVENT_NUMBER`
          GROUP BY `loto`.`T_EVENT_NUMBER`.`LOTO_TYPE_ID`) `l2`)
  WHERE ((`l1`.`LOTO_TYPE_ID` = `l2`.`LOTO_TYPE_ID`) AND (`l1`.`EVENT_NUMBER` = `l2`.`MAX_EVENT_NUMBER`));

