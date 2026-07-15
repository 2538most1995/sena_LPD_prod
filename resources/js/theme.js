import { webDarkTheme, webLightTheme } from '@fluentui/react-components';

const brandOverrides = {
    colorBrandBackground: '#5c2789',
    colorBrandBackgroundHover: '#4b1f72',
    colorBrandBackgroundPressed: '#3c185c',
    colorBrandForeground1: '#5c2789',
    colorBrandForeground2: '#713aa0',
    colorBrandStroke1: '#5c2789',
    colorCompoundBrandForeground1: '#5c2789',
    colorCompoundBrandStroke: '#5c2789',
    colorNeutralForeground1: '#241a29',
    borderRadiusMedium: '10px',
    borderRadiusLarge: '14px',
};

export const senaLightTheme = {
    ...webLightTheme,
    ...brandOverrides,
    colorNeutralBackground1: '#ffffff',
    colorNeutralBackground2: '#f7f4f9',
    colorNeutralBackground3: '#f0eaf3',
};

export const senaDarkTheme = {
    ...webDarkTheme,
    ...brandOverrides,
    colorBrandBackground: '#8a51b6',
    colorBrandBackgroundHover: '#9d64c6',
    colorBrandForeground1: '#c69ee2',
    colorCompoundBrandForeground1: '#c69ee2',
    colorNeutralForeground1: '#f7f2fa',
};
