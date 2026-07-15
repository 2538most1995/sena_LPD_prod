import { webDarkTheme, webLightTheme } from '@fluentui/react-components';

const brandOverrides = {
    fontFamilyBase: "'Noto Sans Thai', Tahoma, system-ui, sans-serif",
    colorBrandBackground: '#5b2a86',
    colorBrandBackgroundHover: '#4b226f',
    colorBrandBackgroundPressed: '#3b1b56',
    colorBrandForeground1: '#5b2a86',
    colorBrandForeground2: '#70409a',
    colorBrandStroke1: '#5b2a86',
    colorCompoundBrandForeground1: '#5b2a86',
    colorCompoundBrandStroke: '#5b2a86',
    colorNeutralForeground1: '#25212b',
    colorNeutralForeground2: '#6f6875',
    colorNeutralStroke1: '#e2dee6',
    colorNeutralStroke2: '#d3ccd9',
    borderRadiusMedium: '10px',
    borderRadiusLarge: '14px',
    borderRadiusXLarge: '18px',
};

export const senaLightTheme = {
    ...webLightTheme,
    ...brandOverrides,
    colorNeutralBackground1: '#ffffff',
    colorNeutralBackground2: '#f6f4f7',
    colorNeutralBackground3: '#efecf2',
};

export const senaDarkTheme = {
    ...webDarkTheme,
    ...brandOverrides,
    colorBrandBackground: '#8a51b6',
    colorBrandBackgroundHover: '#9d64c6',
    colorBrandForeground1: '#c69ee2',
    colorCompoundBrandForeground1: '#c69ee2',
    colorNeutralForeground1: '#f7f2fa',
    colorNeutralForeground2: '#b9adbf',
    colorNeutralBackground1: '#241f27',
    colorNeutralBackground2: '#1a171d',
    colorNeutralBackground3: '#2c2630',
    colorNeutralStroke1: '#403843',
    colorNeutralStroke2: '#504555',
};
