export interface DService {
    key: string;
    name: string;
    description?: string | null;
    url: string;
    /** Built-in icon name (see icons.ts). Ignored when `logo` is set. */
    icon?: string | null;
    /** Image URL for the tile, instead of a built-in icon. */
    logo?: string | null;
    /** Any CSS colour for the icon tile. */
    color?: string | null;
}

/** Shared with every Inertia page as `page.props.dauth`. */
export interface DAuthProps {
    name: string;
    url: string;
    accountUrl: string;
    loginUrl: string | null;
    logoutUrl: string | null;
    services: DService[];
}
