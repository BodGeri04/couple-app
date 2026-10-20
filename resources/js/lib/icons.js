import {
    BookHeart,
    Bell,
    CalendarHeart,
    Camera,
    Coffee,
    Gift,
    Heart,
    House,
    Image,
    MapPin,
    MessageCircle,
    Music,
    Plane,
    ShoppingBasket,
    Sparkles,
    Star,
} from 'lucide-vue-next';

/** Keep in sync with App\Models\MenuItem::ICONS (the server validates against that list). */
export const icons = {
    House,
    CalendarHeart,
    Camera,
    ShoppingBasket,
    Gift,
    Heart,
    Star,
    Bell,
    Image,
    Music,
    MapPin,
    Sparkles,
    MessageCircle,
    BookHeart,
    Coffee,
    Plane,
};

export const iconFor = (name) => icons[name] ?? Star;
