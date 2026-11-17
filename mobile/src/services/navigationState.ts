export interface ShellNavigationState {
  activeTab: string;
  activeDrawerKey: string;
  visitedTabs: Record<string, boolean>;
}

const OWNER_ONLY_TABS = new Set(['purchases', 'reports', 'staff']);

export function createInitialShellState(): ShellNavigationState {
  return {
    activeTab: 'dashboard',
    activeDrawerKey: 'dashboard',
    visitedTabs: { dashboard: true },
  };
}

export function resolveShellStateForTabSelection(
  prev: ShellNavigationState,
  tabKey: string,
  role?: string | null,
  subTab?: string
): ShellNavigationState {
  const isOwner = role === 'owner';

  if (!isOwner && OWNER_ONLY_TABS.has(tabKey)) {
    return createInitialShellState();
  }

  const sanitizedSubTab =
    tabKey === 'settings' && !isOwner && subTab && subTab !== 'profile' ? 'profile' : subTab;

  return {
    activeTab: tabKey,
    activeDrawerKey: sanitizedSubTab ? `${tabKey}:${sanitizedSubTab}` : tabKey,
    visitedTabs: prev.visitedTabs[tabKey]
      ? prev.visitedTabs
      : { ...prev.visitedTabs, [tabKey]: true },
  };
}

