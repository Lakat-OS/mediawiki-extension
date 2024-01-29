create table /*_*/lakat_staging (
	la_id int unsigned auto_increment not null primary key,
	la_branch_name varbinary(255) not null,
	la_name varbinary(255) not null,
	constraint branch_article_unique unique (la_branch_name, la_name)
)/*$wgDBTableOptions*/;
