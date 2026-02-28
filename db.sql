create table if not exists cam_transactions
(
	id serial not null
		constraint ohs_transactions_pkey
			primary key,
	table_name text not null,
	row_id integer not null,
	user_id integer not null,
	user_name text,
	date_open date
);

alter table cam_transactions owner to app_mobile;

create table if not exists cam_roles
(
	id serial not null
		constraint ohs_roles_pkey
			primary key,
	name text not null,
	permissions text not null,
	comment text,
	active integer not null,
	table_name varchar(191) default NULL::character varying,
	row_id integer,
	user_id integer,
	user_name integer,
	created_at timestamp default CURRENT_DATE,
	author integer
);

alter table cam_roles owner to app_mobile;

create table if not exists cam_users
(
	id serial not null
		constraint ohs_users_pkey
			primary key,
	name char(100),
	surname char(100),
	middle_name char(100),
	email char(100),
	phone char(100),
	division integer,
	roles jsonb,
	position text,
	active integer,
	comment text,
	login text,
	password text,
	login_attempts integer default 0,
	locked_until timestamp,
	inn text,
	institution integer,
	created_at timestamp default CURRENT_DATE,
	author integer,
	version integer,
	ousr integer,
	phones text,
	ministries integer
);

alter table cam_users owner to app_mobile;

create table if not exists cam_modules
(
	id serial not null
		constraint cam_modules_pkey
			primary key,
	name text,
	path text,
	active smallint not null
);

alter table cam_modules owner to app_mobile;

create table if not exists cam_usersettings
(
	id serial not null
		constraint cam_usersettings_pkey
			primary key,
	user_id integer not null,
	module_id integer,
	settings text not null
);

alter table cam_usersettings owner to app_mobile;

create table if not exists cam_regprops
(
	id serial not null
		constraint cam_regprops_pk
			primary key,
	name text not null,
	type text not null,
	size integer,
	cols integer,
	rows integer,
	options_list jsonb,
	from_db text,
	from_db_value text,
	to_field text,
	default_value text,
	mask text,
	placeholder text,
	timestamp timestamp default CURRENT_TIMESTAMP,
	author_id integer,
	comment text,
	step integer,
	from_db_text text,
	min_value integer,
	max_value integer,
	active integer,
	checkbox_values jsonb,
	calendar_type text,
	default_currdate integer,
	default_currtime integer,
	default_currdatetime integer,
	label text,
	field_name text,
	radio_values jsonb
);

alter table cam_regprops owner to app_mobile;

create unique index if not exists cam_regprops_id_uindex
	on cam_regprops (id);

create table if not exists cam_registry
(
	id serial not null
		constraint cam_registry_pkey
			primary key,
	parent integer,
	name text not null,
	comment text,
	active integer,
	sort integer default 100,
	roles jsonb,
	in_menu integer default 0,
	icon text,
	table_name text,
	short_name text
);

alter table cam_registry owner to app_mobile;

create table if not exists cam_regfields
(
	id serial not null
		constraint cam_regfields_pk
			primary key,
	reg_id integer not null,
	prop_id integer not null,
	sort integer,
	label text not null,
	required integer,
	"unique" integer
);

alter table cam_regfields owner to app_mobile;

create unique index if not exists cam_regfields_id_uindex
	on cam_regfields (id);

create table if not exists cam_registryitems
(
	id serial not null
		constraint cam_registryitems_pkey
			primary key,
	active integer,
	name text,
	parent integer,
	type integer,
	parent_registry integer,
	parent_items integer,
	comment text
);

alter table cam_registryitems owner to app_mobile;

create table if not exists cam_orgtypes
(
	id serial not null,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	name text,
	checks jsonb,
	okopf integer
);

alter table cam_orgtypes owner to app_mobile;

create unique index if not exists cam_orgtypes_pkey
	on cam_orgtypes (id);

create table if not exists cam_checks
(
	id serial not null
		constraint cam_checks_pkey
			primary key
		constraint "checktypeUnique"
			unique,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	name text,
	constraint check1
		check (true)
);

alter table cam_checks owner to app_mobile;

create table if not exists cam_units
(
	id serial not null
		constraint cam_units_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	location text,
	name text,
	institution integer,
	ministries integer
);

alter table cam_units owner to app_mobile;

create table if not exists cam_checksplans
(
	id serial not null
		constraint cam_checksplans_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 0,
	addinstitution jsonb,
	year integer,
	version integer,
	uid text,
	document integer,
	name integer,
	planname integer,
	short text,
	approve integer,
	doc_number text,
	approved integer,
	checks jsonb,
	importbutton text,
	inspections integer
);

alter table cam_checksplans owner to app_mobile;

create table if not exists cam_inspections
(
	id serial not null
		constraint cam_inspections_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	name text,
	checks integer
);

alter table cam_inspections owner to app_mobile;

create table if not exists cam_templatetext
(
	id serial not null
		constraint cam_templatetext_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	bottom text,
	header text,
	body text
);

alter table cam_templatetext owner to app_mobile;

create table if not exists cam_documents
(
	id serial not null
		constraint cam_documents_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	bottom text,
	header text,
	body text,
	name text,
	documentacial integer,
	initiator integer,
	initiation timestamp,
	consultation integer,
	agreementlist text,
	source_id integer,
	source_table text,
	agreementtemplate integer,
	doc_number text,
	document integer,
	apply text,
	checks integer
);

alter table cam_documents owner to app_mobile;

create table if not exists cam_checkstaff
(
	id serial not null
		constraint cam_checkstaff_pk
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer,
	active integer,
	check_uid text,
	"user" integer,
	dates text,
	institution integer,
	task_id integer,
	unit integer,
	ousr integer,
	object_type integer default 1,
	done integer default 0,
	arrival timestamp,
	longitude text default 0,
	latitude text default 0,
	ending timestamp,
	record_id integer,
	sign jsonb,
	is_head integer,
	ministry integer,
	order_number text,
	order_date date,
	order_template integer,
	order_signers jsonb,
	agreement_template integer,
	agreement_initiator integer,
	agreement_initiation date,
	agreement_brief text,
	addagreement jsonb,
	allowremind integer default 0,
	file_ids jsonb,
	geo_comment text,
	order_id integer
);

alter table cam_checkstaff owner to app_mobile;

create unique index if not exists cam_checkstaff_id_uindex
	on cam_checkstaff (id);

create table if not exists cam_plannames
(
	id serial not null
		constraint cam_plannames_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	longname text,
	name text,
	short text,
	document integer,
	addinstitution jsonb,
	checks integer,
	inspections integer,
	year text
);

alter table cam_plannames owner to app_mobile;

create table if not exists cam_positions
(
	id serial not null
		constraint cam_positions_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	name text,
	comment text
);

alter table cam_positions owner to app_mobile;

create table if not exists cam_departments
(
	id serial not null
		constraint cam_departments_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	name text,
	division integer
);

alter table cam_departments owner to app_mobile;

create table if not exists cam_checklists
(
	id serial not null
		constraint cam_checklists_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer,
	active integer default 1,
	parent integer,
	name text not null,
	comment text,
	sort integer default 100,
	roles jsonb,
	table_name text,
	in_menu integer
);

alter table cam_checklists owner to app_mobile;

create table if not exists cam_checkprops
(
	id serial not null
		constraint cam_checkprops_pk
			primary key,
	active integer,
	author_id integer,
	created_at timestamp default CURRENT_TIMESTAMP,
	name text not null,
	type text not null,
	size integer,
	cols integer,
	rows integer,
	options_list jsonb,
	from_db text,
	from_db_value text,
	to_field text,
	default_value text,
	mask text,
	placeholder text,
	comment text,
	step integer,
	from_db_text text,
	min_value integer,
	max_value integer,
	checkbox_values jsonb,
	calendar_type text,
	default_currdate integer,
	default_currtime integer,
	default_currdatetime integer,
	label text,
	field_name text
);

alter table cam_checkprops owner to app_mobile;

create unique index if not exists cam_checkprops_id_uindex
	on cam_checkprops (id);

create table if not exists cam_checkfields
(
	id serial not null
		constraint cam_checkfields_pk
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer,
	reg_id integer not null,
	prop_id integer not null,
	sort integer,
	label text not null,
	required integer,
	"unique" integer,
	row_behaviour jsonb,
	is_block integer,
	block_id integer
);

alter table cam_checkfields owner to app_mobile;

create unique index if not exists cam_checkfields_id_uindex
	on cam_checkfields (id);

create table if not exists cam_checkitems
(
	id serial not null
		constraint cam_checkitems_pk
			primary key,
	active integer,
	author_id integer,
	created_at timestamp default CURRENT_TIMESTAMP,
	name text not null,
	type text not null,
	size integer,
	cols integer,
	rows integer,
	options_list jsonb,
	from_db text,
	from_db_value text,
	to_field text,
	default_value text,
	mask text,
	placeholder text,
	comment text,
	step integer,
	from_db_text text,
	min_value integer,
	max_value integer,
	checkbox_values jsonb,
	calendar_type text,
	default_currdate integer,
	default_currtime integer,
	default_currdatetime integer,
	label text,
	field_name text,
	is_block integer,
	block_id integer default 0,
	sort integer,
	radio_values jsonb,
	from_db_view integer,
	item_type integer default 0
);

alter table cam_checkitems owner to app_mobile;

create unique index if not exists cam_checkitems_id_uindex
	on cam_checkitems (id);

create table if not exists cam_tasks
(
	id serial not null
		constraint cam_tasks_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	name text,
	finding integer,
	confirm integer,
	comment text,
	sheet jsonb,
	subject jsonb
);

alter table cam_tasks owner to app_mobile;

create table if not exists cam_city
(
	id serial not null
		constraint cam_city_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	name text
);

alter table cam_city owner to app_mobile;

create table if not exists cam_ousr
(
	id serial not null
		constraint cam_ousr_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	name text,
	institution integer
);

alter table cam_ousr owner to app_mobile;

create table if not exists cam_verificationchecks
(
	id serial not null
		constraint cam_verificationchecks_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	name text
);

alter table cam_verificationchecks owner to app_mobile;

create table if not exists cam_parents
(
	id serial not null
		constraint cam_parents_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	name text
);

alter table cam_parents owner to app_mobile;

create table if not exists cam_help
(
	id serial not null
		constraint cam_help_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	name text
);

alter table cam_help owner to app_mobile;

create table if not exists cam_rehabilitation
(
	id serial not null
		constraint cam_rehabilitation_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	name text
);

alter table cam_rehabilitation owner to app_mobile;

create table if not exists cam_situation
(
	id serial not null
		constraint cam_situation_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	name text
);

alter table cam_situation owner to app_mobile;

create table if not exists cam_together
(
	id serial not null
		constraint cam_together_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	name text
);

alter table cam_together owner to app_mobile;

create table if not exists cam_adaptation
(
	id serial not null
		constraint cam_adaptation_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	name text
);

alter table cam_adaptation owner to app_mobile;

create table if not exists cam_peculiarities
(
	id serial not null
		constraint cam_peculiarities_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	name text
);

alter table cam_peculiarities owner to app_mobile;

create table if not exists cam_housing
(
	id serial not null
		constraint cam_housing_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	name text
);

alter table cam_housing owner to app_mobile;

create table if not exists cam_subject
(
	id serial not null
		constraint cam_subject_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	name text
);

alter table cam_subject owner to app_mobile;

create table if not exists cam_notifications
(
	id serial not null
		constraint cam_notifications_pkey
			primary key,
	created_at timestamp default CURRENT_TIMESTAMP not null,
	message text,
	viewed integer default 0,
	user_id integer,
	task_id integer,
	path text
);

alter table cam_notifications owner to app_mobile;

create unique index if not exists cam_notifications_id_uindex
	on cam_notifications (id);

create index if not exists idx_cam_notifications_created_at
	on cam_notifications (created_at);

create table if not exists cam_checksresult
(
	id serial not null
		constraint cam_checksresult_pk
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer,
	user_id integer,
	task_id integer,
	checklist integer,
	result_text text
);

alter table cam_checksresult owner to app_mobile;

create unique index if not exists cam_checksresult_id_uindex
	on cam_checksresult (id);

create table if not exists cam_documentdocuments
(
	id serial not null
		constraint cam_documentdocuments_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	name text
);

alter table cam_documentdocuments owner to app_mobile;

create table if not exists cam_persons
(
	id serial not null
		constraint cam_persons_pkey
			primary key,
	created_at timestamp,
	author integer,
	surname text,
	first_name text,
	middle_name text,
	urban text,
	eais_id integer,
	ousr text,
	inn integer,
	birth date,
	phone text,
	location text,
	address text,
	email text,
	geo_lat text,
	geo_lon text,
	active integer default 1,
	snils double precision
);

alter table cam_persons owner to app_mobile;

create index if not exists index_foreignkey_cam_persons_eais
	on cam_persons (eais_id);

create table if not exists cam_participants
(
	id serial not null
		constraint cam_participants_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	peculiarities integer,
	parents integer,
	situation integer,
	residence integer,
	help integer,
	readiness text,
	rehabilitation integer,
	adaptation integer,
	childrenswelfare integer,
	participants text,
	completion date,
	veteran integer,
	living integer,
	ousr integer,
	beginning date,
	verificationchecks integer,
	housing integer,
	together integer
);

alter table cam_participants owner to app_mobile;

create table if not exists cam_examination
(
	id serial not null
		constraint cam_examination_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	peculiarities integer,
	parents integer,
	situation integer,
	residence integer,
	help integer,
	readiness text,
	rehabilitation integer,
	adaptation integer,
	childrenswelfare integer,
	participants text,
	completion date,
	veteran integer,
	living integer,
	ousr integer,
	beginning date,
	verificationchecks integer,
	housing integer,
	together integer
);

alter table cam_examination owner to app_mobile;

create table if not exists cam_checkssc
(
	id serial not null
		constraint cam_checkssc_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	territory text,
	areas text,
	absence integer,
	own integer,
	content integer,
	cleanroads integer,
	duplication integer,
	performance integer,
	accessibility integer,
	informational integer,
	vehicles integer,
	independent integer,
	subanated integer,
	faulty integer,
	security integer,
	lighting integer,
	damage integer,
	auxiliary integer,
	transportation integer,
	recipients integer,
	provided integer,
	disinfectants integer,
	facilities integer,
	accessories integer
);

alter table cam_checkssc owner to app_mobile;

create table if not exists cam_compliance
(
	id serial not null
		constraint cam_compliance_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	responsible integer,
	conducting integer,
	personaldb integer,
	personal integer,
	districtpersonnel text,
	resolution integer,
	unauthorized integer,
	statement integer,
	encouragingpremises integer,
	timely integer,
	devices integer,
	examput integer,
	postatisted integer,
	psastate integer,
	state integer,
	administrators integer,
	provides integer,
	legislation integer,
	installed integer,
	datasafety integer,
	organization integer,
	authentication integer,
	dressing integer,
	limitation integer,
	safetysafety integer,
	usage integer,
	protected integer,
	securityinformation integer
);

alter table cam_compliance owner to app_mobile;

create table if not exists cam_test
(
	id serial not null
		constraint cam_test_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	peculiarities integer,
	responsible integer,
	conducting integer,
	personaldb integer,
	personal integer,
	parents integer,
	situation integer,
	districtpersonnel text,
	resolution integer,
	unauthorized integer,
	statement integer,
	encouragingpremises integer,
	residence integer,
	timely integer,
	help integer,
	readiness text,
	rehabilitation integer,
	devices integer,
	examput integer,
	postatisted integer,
	psastate integer,
	state integer,
	administrators integer,
	adaptation integer,
	childrenswelfare integer,
	provides integer,
	legislation integer,
	installed integer,
	datasafety integer,
	organization integer,
	authentication integer,
	dressing integer,
	limitation integer,
	safetysafety integer,
	usage integer,
	protected integer,
	securityinformation integer,
	participants text,
	completion date,
	veteran integer,
	living integer,
	ousr integer,
	beginning date,
	verificationchecks integer,
	housing integer,
	together integer
);

alter table cam_test owner to app_mobile;

create table if not exists cam_signs
(
	id serial not null
		constraint cam_signs_pk
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	doc_id integer not null,
	sign jsonb not null,
	user_id integer not null,
	type integer default 2,
	table_name text,
	section integer
);

alter table cam_signs owner to app_mobile;

create unique index if not exists cam_signs_id_uindex
	on cam_signs (id);

create table if not exists cam_checkinstitutions
(
	id serial not null
		constraint cam_checkinstitutions_pk
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer,
	institution integer,
	check_types integer,
	units integer,
	periods text,
	periods_hidden jsonb,
	inspections jsonb,
	check_periods_start date,
	check_periods_end date,
	plan_uid varchar,
	plan_version integer not null
);

alter table cam_checkinstitutions owner to app_mobile;

create unique index if not exists cam_checkinstitutions_id_uindex
	on cam_checkinstitutions (id);

create table if not exists cam_agreement
(
	id serial not null
		constraint cam_docs_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	name text,
	document integer,
	documentacial integer,
	doc_number text,
	bottom text,
	header text,
	body text,
	status integer default 0,
	source_id integer,
	source_table text,
	docdate date,
	act_agree integer,
	brief text,
	initiator integer,
	initiation timestamp,
	agreementlist jsonb,
	agreementtemplate integer,
	apply text,
	signators text,
	executors_list jsonb,
	executors_head integer,
	plan_id integer,
	ins_id integer,
	prev_ins_id integer,
	unit_id integer,
	check_period text,
	action_period text,
	action_period_text text,
	files_ids jsonb,
	edited_at timestamp with time zone default CURRENT_DATE
);

alter table cam_agreement owner to app_mobile;

create table if not exists cam_coordination
(
	id serial not null
		constraint cam_coordination_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	initiator integer,
	initiation timestamp,
	agreementlist jsonb,
	agreementtemplate integer,
	apply text
);

alter table cam_coordination owner to app_mobile;

create table if not exists cam_ministries
(
	id serial not null
		constraint cam_ministries_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	name text,
	institution integer
);

alter table cam_ministries owner to app_mobile;

create table if not exists cam_characteristic
(
	id serial not null
		constraint cam_characteristic_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	characteristic text,
	ministry integer,
	official integer,
	serviceregistered integer,
	carryingout integer,
	carriedout integer,
	registered integer,
	starcapacity integer,
	activity integer,
	events integer,
	deadlines integer,
	control integer,
	realuated integer,
	units integer,
	introductory text,
	spent text,
	information text,
	violations text
);

alter table cam_characteristic owner to app_mobile;

create table if not exists cam_fhdactivity
(
	id serial not null
		constraint cam_fhdactivity_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	fhdactivity text,
	theservicefull integer,
	performed integer,
	nonfulfillment text,
	execution text,
	informationinformation text,
	violationsof text,
	application text,
	activityintro text,
	serviceservice text,
	services integer,
	provision integer,
	accompaniment integer,
	hippupublication integer,
	prelawsuits integer,
	recommended integer,
	actservice integer,
	educational integer,
	provisionofpredicted integer,
	formed integer,
	violationsofmanagement text,
	file_ids json,
	maininformation text,
	urgentservice integer
);

alter table cam_fhdactivity owner to app_mobile;

create table if not exists cam_service
(
	id serial not null
		constraint cam_service_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	applications text,
	activityintro text,
	theservicefull integer,
	performed integer,
	nonfulfillment text,
	application integer,
	execution integer,
	serviceservice text,
	service text,
	services integer,
	provision integer,
	accompaniment integer,
	hippupublication integer,
	prelawsuits integer,
	recommended integer,
	actservice integer,
	urgentservice integer,
	educational integer,
	provisionofpredicted integer,
	formed text,
	maininformation text,
	violationsofmanagement text,
	file_ids json
);

alter table cam_service owner to app_mobile;

create table if not exists cam_costestimates
(
	id serial not null
		constraint cam_costestimates_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	institutions text,
	introofinstitution text,
	formation integer,
	obligations integer,
	ministryofdevelopment integer,
	justifications integer,
	approved integer,
	schedulesaffirmed integer,
	indicatorsaffirmed integer,
	medicated integer,
	ministryofsocialdevelopment integer,
	actualized integer,
	ministryofsocialdevelop integer,
	produced integer,
	moreinformation text,
	represented text,
	identifiedviolations text,
	budgetestimates text,
	characteristic text,
	ministry integer,
	official integer,
	serviceregistered integer,
	carryingout integer,
	carriedout integer,
	registered integer,
	starcapacity integer,
	activity integer,
	events integer,
	deadlines integer,
	control integer,
	realuated integer,
	units integer,
	violations text,
	spent text,
	information text,
	introductory text
);

alter table cam_costestimates owner to app_mobile;

create table if not exists cam_institutions
(
	id serial not null
		constraint cam_institutions_pkey
			primary key,
	created_at timestamp,
	author integer,
	name text,
	short text,
	inn text,
	orgtype text,
	vehicles integer,
	inspectors integer,
	kpp integer,
	phones text,
	email text,
	agreements_number text,
	agreements text,
	leader text,
	leader_email text,
	jar text,
	correspondent integer,
	calculated double precision,
	location text,
	legal text,
	target_address text,
	active integer default 1,
	geo_lat double precision,
	geo_lon double precision,
	capacity integer,
	eaisid integer,
	"branchId" integer,
	branchname text,
	branchid integer,
	branch_adress text,
	eais_id integer,
	subsidies double precision,
	superior integer,
	successor integer,
	ogrn double precision
);

alter table cam_institutions owner to app_mobile;

create index if not exists index_foreignkey_cam_institutions_eais
	on cam_institutions (eais_id);

create table if not exists institutions
(
	id serial not null
		constraint institutions_pkey
			primary key,
	name varchar(500) not null,
	type varchar(100) not null,
	region varchar(100) not null,
	address text,
	director varchar(200),
	phone varchar(20),
	email varchar(100),
	created_at timestamp default CURRENT_TIMESTAMP
);

alter table institutions owner to app_mobile;

create table if not exists inspection_types
(
	id serial not null
		constraint inspection_types_pkey
			primary key,
	name varchar(200) not null,
	description text,
	frequency_months integer
);

alter table inspection_types owner to app_mobile;

create table if not exists inspections
(
	id serial not null
		constraint inspections_pkey
			primary key,
	institution_id integer
		constraint inspections_institution_id_fkey
			references institutions
				on delete cascade,
	type_id integer
		constraint inspections_type_id_fkey
			references inspection_types
				on delete set null,
	start_date date not null,
	end_date date not null,
	status varchar(50) not null
		constraint inspections_status_check
			check ((status)::text = ANY ((ARRAY['planned'::character varying, 'in_progress'::character varying, 'completed'::character varying, 'cancelled'::character varying])::text[])),
	inspector_name varchar(200) not null,
	result varchar(50)
		constraint inspections_result_check
			check ((result)::text = ANY ((ARRAY['no_violations'::character varying, 'violations_found'::character varying, 'critical_violations'::character varying])::text[])),
	notes text,
	created_at timestamp default CURRENT_TIMESTAMP
);

alter table inspections owner to app_mobile;

create table if not exists violations
(
	id serial not null
		constraint violations_pkey
			primary key,
	inspection_id integer
		constraint violations_inspection_id_fkey
			references inspections
				on delete cascade,
	type varchar(100) not null,
	description text not null,
	severity varchar(20) not null
		constraint violations_severity_check
			check ((severity)::text = ANY ((ARRAY['low'::character varying, 'medium'::character varying, 'high'::character varying])::text[])),
	deadline date,
	is_fixed boolean default false,
	fix_description text,
	fix_date date,
	created_at timestamp default CURRENT_TIMESTAMP
);

alter table violations owner to app_mobile;

create table if not exists report_templates
(
	id serial not null
		constraint report_templates_pkey
			primary key,
	name varchar(200) not null,
	description text,
	base_query text not null,
	created_at timestamp default CURRENT_TIMESTAMP
);

alter table report_templates owner to app_mobile;

create table if not exists report_fields
(
	id serial not null
		constraint report_fields_pkey
			primary key,
	template_id integer
		constraint report_fields_template_id_fkey
			references report_templates
				on delete cascade,
	field_name varchar(100) not null,
	display_name varchar(100) not null,
	is_visible boolean default true,
	display_order integer not null,
	data_type varchar(50) not null,
	aggregation_type varchar(50),
	created_at timestamp default CURRENT_TIMESTAMP
);

alter table report_fields owner to app_mobile;

create table if not exists reports
(
	id serial not null
		constraint reports_pkey
			primary key,
	template_id integer
		constraint reports_template_id_fkey
			references report_templates
				on delete set null,
	name varchar(200) not null,
	format varchar(20) not null
		constraint reports_format_check
			check ((format)::text = ANY ((ARRAY['full'::character varying, 'short'::character varying])::text[])),
	parameters jsonb,
	created_by varchar(100) not null,
	created_at timestamp default CURRENT_TIMESTAMP,
	file_path varchar(500),
	is_dashboard_visible boolean default false,
	schedule varchar(100)
);

alter table reports owner to app_mobile;

create table if not exists report_nested_rows
(
	id serial not null
		constraint report_nested_rows_pkey
			primary key,
	report_id integer
		constraint report_nested_rows_report_id_fkey
			references reports
				on delete cascade,
	parent_id integer
		constraint report_nested_rows_parent_id_fkey
			references report_nested_rows
				on delete cascade,
	level integer not null,
	row_data jsonb not null,
	sort_order integer not null,
	created_at timestamp default CURRENT_TIMESTAMP
);

alter table report_nested_rows owner to app_mobile;

create table if not exists institution
(
	id serial not null
		constraint institution_pkey
			primary key,
	name varchar(500) not null,
	type varchar(100) not null,
	region varchar(100) not null,
	address text,
	director varchar(200),
	phone varchar(20),
	email varchar(100),
	created_at timestamp default CURRENT_TIMESTAMP
);

alter table institution owner to app_mobile;

create table if not exists cam_api
(
	id serial not null
		constraint cam_api_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	name text,
	key text,
	url text,
	request text
);

alter table cam_api owner to app_mobile;

create table if not exists cam_insadress
(
	id serial not null
		constraint cam_insadress_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer default 1 not null,
	active integer default 1,
	name text,
	inn double precision,
	target_address text,
	basic integer
);

alter table cam_insadress owner to app_mobile;

create table if not exists cam_reminders
(
	id serial not null
		constraint cam_reminders_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	comment text,
	employee integer,
	datetime timestamp,
	task_id integer,
	email text,
	letter text,
	message text,
	url text,
	caption text
);

alter table cam_reminders owner to app_mobile;

create table if not exists cam_reports
(
	id serial not null
		constraint cam_reports_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	name text,
	place integer,
	roles jsonb,
	description text,
	graphics integer,
	periods text,
	sorting text,
	indicator integer,
	comment text,
	ordinal integer
);

alter table cam_reports owner to app_mobile;

create table if not exists cam_graphs
(
	id serial not null
		constraint cam_graphs_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	name text,
	source text
);

alter table cam_graphs owner to app_mobile;

create table if not exists cam_indicators
(
	id serial not null
		constraint cam_indicators_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	name text
);

alter table cam_indicators owner to app_mobile;

create table if not exists cam_violations
(
	id serial not null
		constraint cam_violations_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	name text,
	financial integer
);

alter table cam_violations owner to app_mobile;

create table if not exists cam_subscriptions
(
	id serial not null
		constraint push_subscriptions_pkey
			primary key,
	user_id integer not null
		constraint push_subscriptions_user_id_fkey
			references cam_users
				on delete cascade,
	endpoint varchar(512) not null,
	p256dh varchar(255) not null,
	auth varchar(255) not null,
	created_at timestamp with time zone default CURRENT_TIMESTAMP,
	updated_at timestamp with time zone default CURRENT_TIMESTAMP,
	constraint unique_subscription
		unique (user_id, endpoint)
);

alter table cam_subscriptions owner to app_mobile;

create table if not exists cam_tasklog
(
	id serial not null
		constraint cam_tasklog_pkey
			primary key,
	author integer,
	created_at text,
	task_id integer,
	action text,
	module text,
	form_id text
);

alter table cam_tasklog owner to app_mobile;

create index if not exists index_foreignkey_cam_tasklog_task
	on cam_tasklog (task_id);

create table if not exists cam_files
(
	id serial not null
		constraint cam_files_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	original_filename text,
	system_filename text,
	file_path text,
	mime_type text,
	size integer,
	userid integer,
	name text
);

alter table cam_files owner to app_mobile;

create table if not exists cam_checksviolations
(
	id serial not null
		constraint cam_checksviolations_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	name text,
	tasks integer,
	violations integer,
	checklist integer,
	objections text
);

alter table cam_checksviolations owner to app_mobile;

create table if not exists cam_krasnoshchekova
(
	id serial not null
		constraint cam_krasnoshchekova_pkey
			primary key,
	created_at timestamp default CURRENT_DATE,
	author integer not null,
	active integer default 1,
	territory text,
	areas text,
	absence integer,
	own integer,
	content integer,
	cleanroads integer,
	duplication integer,
	performance integer,
	accessibility integer,
	informational integer,
	vehicles integer,
	independent integer,
	subanated integer,
	faulty integer,
	security integer,
	lighting integer,
	damage integer,
	auxiliary integer,
	transportation integer,
	recipients integer,
	provided integer,
	disinfectants integer,
	facilities integer,
	accessories integer
);

alter table cam_krasnoshchekova owner to app_mobile;

