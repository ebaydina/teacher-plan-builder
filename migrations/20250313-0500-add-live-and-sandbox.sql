
drop table stripe;

create table stripe_live
(
    id            bigint unsigned auto_increment
        primary key,
    customer_id   varchar(255) null,
    user_id       int          not null,
    customer_json json         null,
    constraint stripe_live_customer_id_uindex
        unique (customer_id),
    constraint stripe_live_user_id_uindex
        unique (user_id)
);

create index stripe_live_user_id_customer_id_index
    on stripe_live (user_id, customer_id);

create table stripe_sandbox
(
    id            bigint unsigned auto_increment
        primary key,
    customer_id   varchar(255) null,
    user_id       int          not null,
    customer_json json         null,
    constraint stripe_sandbox_customer_id_uindex
        unique (customer_id),
    constraint stripe_sandbox_user_id_uindex
        unique (user_id)
);

create index stripe_sandbox_user_id_customer_id_index
    on stripe_sandbox (user_id, customer_id);
