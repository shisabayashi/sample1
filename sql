# my.conf修正
[mysqld]
lower_case_table_names=1

# mysql root実行
create database loto;

# ユーザー作成(権限付与)
GRANT CREATE, CREATE VIEW, SHOW VIEW, INDEX, SELECT, INSERT, UPDATE, DELETE ON loto.* TO u_loto@localhost IDENTIFIED BY 'aaaa';
GRANT ALL PRIVILEGES ON loto.* TO u_loto@'localhost' IDENTIFIED BY 'aaaaa' WITH GRANT OPTION;

# table 作成
-- auto-generated definition
create table m_loto_type
(
	id int(2) not null
		primary key,
	loto_type_name varchar(30) null,
	update_date datetime default current_timestamp null,
	insert_date datetime default current_timestamp null,
	active_flag varchar(1) default 'y' null
)engine=innodb default charset=utf8
;

###

-- auto-generated definition
create table t_event_number
(
	id int(7) not null auto_increment
		primary key,
	event_number int(7) null,
	loto_date date null,
	loto_type_id int(2) null,
	update_date datetime default current_timestamp null,
	insert_date datetime default current_timestamp null,
	active_flag varchar(1) default 'y' null,
	constraint t_event_number_m_loto_type_id_fk
		foreign key (loto_type_id) references loto.m_loto_type (id)
)
;

create index t_event_number_m_loto_type_id_fk
	on t_event_number (loto_type_id)
;

###

-- auto-generated definition
create table t_lottery_result
(
	id int(7) not null auto_increment
		primary key,
	event_number_id int(7) null,
	loto_nubers varchar(30) null,
	bonus_nubers varchar(15) null,
	update_date datetime default current_timestamp null,
	insert_date datetime default current_timestamp null,
	active_flag varchar(1) default 'y' null,
	constraint t_lottery_result_t_event_number_id_fk
		foreign key (event_number_id) references loto.t_event_number (id)
)
;

create index t_lottery_result_t_event_number_id_fk
	on t_lottery_result (event_number_id)
;



# view 作成
create view v_latest_event as
  select
    `l1`.`loto_type_id` as `loto_type_id`,
    `l1`.`loto_date`    as `loto_date`,
    `l1`.`event_number` as `event_number`
  from (`loto`.`t_event_number` `l1`
    join (select
            `loto`.`t_event_number`.`loto_type_id`      as `loto_type_id`,
            max(`loto`.`t_event_number`.`event_number`) as `max_event_number`
          from `loto`.`t_event_number`
          group by `loto`.`t_event_number`.`loto_type_id`) `l2`)
  where ((`l1`.`loto_type_id` = `l2`.`loto_type_id`) and (`l1`.`event_number` = `l2`.`max_event_number`));

