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
        view_complaint_sources: boolean;
        create_complaint_sources: boolean;
        update_complaint_sources: boolean;
        delete_complaint_sources: boolean;
        view_complaint_powers: boolean;
        create_complaint_powers: boolean;
        update_complaint_powers: boolean;
        delete_complaint_powers: boolean;
        view_complaint_step_statuses: boolean;
        create_complaint_step_statuses: boolean;
        update_complaint_step_statuses: boolean;
        delete_complaint_step_statuses: boolean;
        view_sku_codes: boolean;
        create_sku_codes: boolean;
        update_sku_codes: boolean;
        delete_sku_codes: boolean;
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
        view_reason_late_responses: boolean;
        create_reason_late_responses: boolean;
        update_reason_late_responses: boolean;
        delete_reason_late_responses: boolean;
        view_order_tracking_data_sources: boolean;
        create_order_tracking_data_sources: boolean;
        update_order_tracking_data_sources: boolean;
        delete_order_tracking_data_sources: boolean;
        import_order_trackings: boolean;
        export_order_trackings: boolean;
        delete_order_trackings: boolean;
        view_order_tracking_erp_statuses: boolean;
        create_order_tracking_erp_statuses: boolean;
        update_order_tracking_erp_statuses: boolean;
        delete_order_tracking_erp_statuses: boolean;
        import_order_tracking_erp_statuses: boolean;
        view_order_tracking_rgo_entries: boolean;
        create_order_tracking_rgo_entries: boolean;
        update_order_tracking_rgo_entries: boolean;
        delete_order_tracking_rgo_entries: boolean;
        import_order_tracking_rgo_entries: boolean;
        view_jet_track_entries: boolean;
        create_jet_track_entries: boolean;
        update_jet_track_entries: boolean;
        delete_jet_track_entries: boolean;
        import_jet_track_entries: boolean;
        view_oos_reasons: boolean;
        create_oos_reasons: boolean;
        update_oos_reasons: boolean;
        delete_oos_reasons: boolean;
        view_oos_solutions: boolean;
        create_oos_solutions: boolean;
        update_oos_solutions: boolean;
        delete_oos_solutions: boolean;
        view_cause_bys: boolean;
        create_cause_bys: boolean;
        update_cause_bys: boolean;
        delete_cause_bys: boolean;
        view_users: boolean;
        create_users: boolean;
        update_users: boolean;
        delete_users: boolean;
        view_dashboard: boolean;
        access_complaints: boolean;
        import_complaints: boolean;
        export_complaints: boolean;
        access_bad_reviews: boolean;
        import_bad_reviews: boolean;
        export_bad_reviews: boolean;
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

export interface SharedData extends Record<string, unknown> {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    features: {
        public_registration: boolean;
    };
    flash: {
        success?: string;
        error?: string;
        import_result?: {
            updated?: number;
            created?: number;
            pending?: number;
            skipped?: number;
            failed?: number;
            errors?: string[];
            ordered_statuses?: string[];
        };
        erp_import_result?: {
            updated?: number;
            pending?: number;
            skipped?: number;
            failed?: number;
            errors?: string[];
            ordered_statuses?: string[];
        };
        rgo_import_result?: {
            updated?: number;
            created?: number;
            skipped?: number;
            failed?: number;
            errors?: string[];
        };
        rgo_sync_result?: {
            updated?: number;
            skipped?: number;
            errors?: string[];
        };
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
