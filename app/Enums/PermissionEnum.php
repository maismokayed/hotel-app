<?php

namespace App\Enums;

enum PermissionEnum: string
{
case MANAGE_ROLES = 'manage_roles';
case MANAGE_PERMISSIONS = 'manage_permissions'; 
case MANAGE_USERS = 'manage_users';
case VIEW_USERS = 'view_users';
case VIEW_OWN_PROFILE = 'view_own_profile';
case UPDATE_OWN_PROFILE = 'update_own_profile';
case VIEW_HOTELS = 'view_hotels';
case CREATE_HOTELS = 'create_hotels';
case UPDATE_HOTELS = 'update_hotels';
case DELETE_HOTELS = 'delete_hotels';
case MANAGE_OWN_HOTEL = 'manage_own_hotel';
case VIEW_ROOMS = 'view_rooms';
case CREATE_ROOMS = 'create_rooms';
case UPDATE_ROOMS = 'update_rooms';
case DELETE_ROOMS = 'delete_rooms';
case MANAGE_OWN_ROOMS = 'manage_own_rooms';
case MANAGE_MEDIA = 'manage_media';
case UPLOAD_MEDIA = 'upload_media';
case DELETE_MEDIA = 'delete_media';
case VIEW_BOOKINGS = 'view_bookings';
case CREATE_BOOKINGS = 'create_bookings';
case UPDATE_BOOKINGS = 'update_bookings';
case CANCEL_BOOKINGS = 'cancel_bookings';
case VIEW_OWN_BOOKINGS = 'view_own_bookings';
case VIEW_OWN_WALLET = 'view_own_wallet';
case VIEW_OWN_WALLET_TRANSACTIONS = 'view_own_wallet_transactions';
case TOP_UP_WALLET = 'top_up_wallet'; 
//case WITHDRAW_WALLET = 'withdraw_wallet';
case VIEW_REVIEWS = 'view_reviews';
case CREATE_REVIEWS = 'create_reviews';
case UPDATE_REVIEWS = 'update_reviews';
case DELETE_REVIEWS = 'delete_reviews';
case VIEW_COUPONS = 'view_coupons';
case CREATE_COUPONS = 'create_coupons';
case UPDATE_COUPONS = 'update_coupons';
case DELETE_COUPONS = 'delete_coupons';
case APPLY_COUPON = 'apply_coupon';
}