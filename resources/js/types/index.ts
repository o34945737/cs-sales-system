import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User | null;
    can: {
        view_brands: boolean;
        create_brands: boolean;
        update_brands: boolean;
        delete_brands: boolean;
        view_platforms: boolean;
        create_platforms: boolean;
        update_platforms: boolean;
        delete_platforms: boolean;
        view_logistics: boolean;
        create_logistics: boolean;
        update_logistics: boolean;
        delete_logistics: boolean;
        view_sub_cases: boolean;
        create_sub_cases: boolean;
        update_sub_cases: boolean;
        delete_sub_cases: boolean;
        view_last_steps: boolean;
        create_last_steps: boolean;
        update_last_steps: boolean;
        delete_last_steps: boolean;
        view_reason_whitelists: boolean;
        create_reason_whitelists: boolean;
        update_reason_whitelists: boolean;
        delete_reason_whitelists: boolean;
        view_users: boolean;
        create_users: boolean;
        update_users: boolean;
        delete_users: boolean;
        view_dashboard: boolean;
        access_complaints: boolean;
        access_bad_reviews: boolean;
        access_order_trackings: boolean;
        access_oos: boolean;
    };
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    features: {
        public_registration: boolean;
    };
    flash: {
        success?: string;
        error?: string;
    };
    ziggy: {
        location: string;
        url: string;
        port: null | number;
        defaults: Record<string, unknown>;
        routes: Record<string, string>;
    };
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    is_active: boolean;
    force_password_reset: boolean;
    last_login_at: string | null;
    last_login_ip: string | null;
    last_login_user_agent: string | null;
    roles: string[];
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;
