import React, { forwardRef } from 'react';
import {
    Alert,
    Avatar as MuiAvatar,
    Box,
    Button as MuiButton,
    Card as MuiCard,
    Chip,
    CircularProgress,
    CssBaseline,
    Dialog as MuiDialog,
    DialogActions as MuiDialogActions,
    DialogContent as MuiDialogContent,
    DialogTitle as MuiDialogTitle,
    FormControl,
    FormHelperText,
    FormLabel,
    IconButton,
    InputAdornment,
    Skeleton as MuiSkeleton,
    Tab as MuiTab,
    Tabs as MuiTabs,
    TextField,
    ThemeProvider,
} from '@mui/material';

const initials = (name = '') => name.trim().split(/\s+/).slice(0, 2).map((part) => part[0]).join('');

export function SenaProvider({ theme, className, children }) {
    return (
        <ThemeProvider theme={theme}>
            <CssBaseline />
            <div className={className}>{children}</div>
        </ThemeProvider>
    );
}

export const Button = forwardRef(function Button({
    appearance = 'secondary',
    icon,
    children,
    size = 'medium',
    className = '',
    ...props
}, ref) {
    const muiSize = size === 'large' ? 'large' : size === 'small' ? 'small' : 'medium';
    if (!children || className.includes('topbar-action')) {
        return <IconButton ref={ref} className={className} size={muiSize} {...props}>{icon}{children}</IconButton>;
    }

    const variant = appearance === 'primary' ? 'contained' : appearance === 'secondary' ? 'outlined' : 'text';
    return <MuiButton ref={ref} className={className} variant={variant} size={muiSize} startIcon={icon} disableElevation {...props}>{children}</MuiButton>;
});

export const Card = forwardRef(function Card({ children, ...props }, ref) {
    return <MuiCard ref={ref} variant="outlined" {...props}>{children}</MuiCard>;
});

export function Field({ label, required, hint, validationMessage, children, className }) {
    const error = Boolean(validationMessage);
    return (
        <FormControl className={className} fullWidth required={required} error={error}>
            {label ? <FormLabel>{label}</FormLabel> : null}
            {children}
            {error || hint ? <FormHelperText>{validationMessage || hint}</FormHelperText> : null}
        </FormControl>
    );
}

export const Input = forwardRef(function Input({ contentBefore, contentAfter, onChange, size = 'medium', readOnly, min, max, step, inputMode, ...props }, ref) {
    return (
        <TextField
            inputRef={ref}
            fullWidth
            size={size === 'large' ? 'medium' : 'small'}
            onChange={(event) => onChange?.(event, { value: event.target.value })}
            slotProps={{
                input: {
                    startAdornment: contentBefore ? <InputAdornment position="start">{contentBefore}</InputAdornment> : undefined,
                    endAdornment: contentAfter ? <InputAdornment position="end">{contentAfter}</InputAdornment> : undefined,
                },
                htmlInput: { readOnly, min, max, step, inputMode },
            }}
            {...props}
        />
    );
});

export const Textarea = forwardRef(function Textarea({ onChange, rows = 3, ...props }, ref) {
    return (
        <TextField
            inputRef={ref}
            fullWidth
            multiline
            minRows={rows}
            size="small"
            onChange={(event) => onChange?.(event, { value: event.target.value })}
            {...props}
        />
    );
});

export function Dialog({ open, onOpenChange, children }) {
    return (
        <MuiDialog
            open={open}
            maxWidth={false}
            onClose={(event) => onOpenChange?.(event, { open: false })}
            slotProps={{ paper: { className: 'ui-dialog-paper' }, backdrop: { className: 'ui-dialog-backdrop' } }}
        >
            {children}
        </MuiDialog>
    );
}

export function DialogSurface({ className = '', children }) {
    return <Box className={`ui-dialog-surface ${className}`}>{children}</Box>;
}

export function DialogBody({ children }) {
    return <>{children}</>;
}

export function DialogTitle({ children }) {
    return <MuiDialogTitle>{children}</MuiDialogTitle>;
}

export function DialogContent({ className, children }) {
    return <MuiDialogContent className={className}>{children}</MuiDialogContent>;
}

export function DialogActions({ children }) {
    return <MuiDialogActions>{children}</MuiDialogActions>;
}

export function Avatar({ name, image, size = 40, color: _color, ...props }) {
    return <MuiAvatar src={image?.src} alt={name} sx={{ width: size, height: size }} {...props}>{initials(name)}</MuiAvatar>;
}

const chipColor = {
    brand: 'primary',
    informative: 'info',
    success: 'success',
    warning: 'warning',
    danger: 'error',
    subtle: 'default',
};

export function Badge({ color = 'subtle', appearance = 'tint', size = 'small', children, className }) {
    return (
        <Chip
            className={className}
            label={children}
            color={chipColor[color] || 'default'}
            variant={appearance === 'filled' ? 'filled' : 'outlined'}
            size={size === 'large' ? 'medium' : 'small'}
        />
    );
}

export function Spinner({ label, size = 'medium' }) {
    return <span className="ui-spinner"><CircularProgress size={size === 'tiny' ? 16 : 24} thickness={4} /><span>{label}</span></span>;
}

export function MessageBar({ intent = 'info', children }) {
    return <Alert severity={intent} variant="outlined">{children}</Alert>;
}

export function MessageBarBody({ children }) {
    return <>{children}</>;
}

export function TabList({ selectedValue, onTabSelect, children }) {
    return <MuiTabs value={selectedValue} onChange={(event, value) => onTabSelect?.(event, { value })} variant="scrollable" scrollButtons="auto">{children}</MuiTabs>;
}

export function Tab({ value, children }) {
    return <MuiTab value={value} label={children} disableRipple />;
}

export function Skeleton({ className, children }) {
    return <Box className={className}>{children}</Box>;
}

export function SkeletonItem({ size = 16 }) {
    return <MuiSkeleton variant="rounded" height={size} animation="wave" />;
}
