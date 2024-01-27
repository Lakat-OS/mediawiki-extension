create table /*_*/lakat_article (
	la_id int unsigned auto_increment not null primary key,
	la_branch_name varbinary(255) not null,
	la_name varbinary(255) not null,
	la_last_rev_id int unsigned not null,
	la_sync_rev_id int unsigned null,
	constraint branch_article_unique unique (la_branch_name, la_name)
)/*$wgDBTableOptions*/;
