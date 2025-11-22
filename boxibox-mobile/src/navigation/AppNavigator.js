import React from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createNativeStackNavigator } from '@react-navigation/native-stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { useAuth } from '../context/AuthContext';
import Loading from '../components/Loading';
import { COLORS } from '../constants/colors';

// Auth Screens
import LoginScreen from '../screens/LoginScreen';
import RegisterScreen from '../screens/RegisterScreen';

// Main Screens
import DashboardScreen from '../screens/DashboardScreen';
import ContractsScreen from '../screens/ContractsScreen';
import ContractDetailsScreen from '../screens/ContractDetailsScreen';
import InvoicesScreen from '../screens/InvoicesScreen';
import InvoiceDetailsScreen from '../screens/InvoiceDetailsScreen';
import ProfileScreen from '../screens/ProfileScreen';

// Profile & Account Screens
import EditProfileScreen from '../screens/EditProfileScreen';
import ChangePasswordScreen from '../screens/ChangePasswordScreen';

// Issue Screens
import IssuesScreen from '../screens/IssuesScreen';
import IssueDetailsScreen from '../screens/IssueDetailsScreen';
import CreateIssueScreen from '../screens/CreateIssueScreen';

// Other Screens
import LoyaltyScreen from '../screens/LoyaltyScreen';
import PromotionsScreen from '../screens/PromotionsScreen';
import PaymentRemindersScreen from '../screens/PaymentRemindersScreen';

// Reservation Screens
import SearchBoxesScreen from '../screens/SearchBoxesScreen';
import BoxDetailsScreen from '../screens/BoxDetailsScreen';
import ReservationsScreen from '../screens/ReservationsScreen';

// Contract Termination
import TerminateContractScreen from '../screens/TerminateContractScreen';

const Stack = createNativeStackNavigator();
const Tab = createBottomTabNavigator();

// Tab Navigator for authenticated users
const MainTabs = () => {
  return (
    <Tab.Navigator
      screenOptions={{
        headerShown: false,
        tabBarActiveTintColor: COLORS.primary,
        tabBarInactiveTintColor: COLORS.gray[500],
        tabBarStyle: {
          backgroundColor: COLORS.white,
          borderTopColor: COLORS.border,
          paddingBottom: 5,
          paddingTop: 5,
          height: 60,
        },
        tabBarLabelStyle: {
          fontSize: 12,
          fontWeight: '600',
        },
      }}
    >
      <Tab.Screen
        name="DashboardTab"
        component={DashboardScreen}
        options={{
          tabBarLabel: 'Accueil',
          title: 'Tableau de bord',
        }}
      />
      <Tab.Screen
        name="ContractsTab"
        component={ContractsScreen}
        options={{
          tabBarLabel: 'Contrats',
          title: 'Mes contrats',
        }}
      />
      <Tab.Screen
        name="InvoicesTab"
        component={InvoicesScreen}
        options={{
          tabBarLabel: 'Factures',
          title: 'Mes factures',
        }}
      />
      <Tab.Screen
        name="ProfileTab"
        component={ProfileScreen}
        options={{
          tabBarLabel: 'Profil',
          title: 'Mon profil',
        }}
      />
    </Tab.Navigator>
  );
};

// Auth Navigator
const AuthNavigator = () => {
  return (
    <Stack.Navigator
      screenOptions={{
        headerShown: false,
      }}
    >
      <Stack.Screen name="Login" component={LoginScreen} />
      <Stack.Screen name="Register" component={RegisterScreen} />
    </Stack.Navigator>
  );
};

// Main Navigator
const MainNavigator = () => {
  return (
    <Stack.Navigator
      screenOptions={{
        headerStyle: {
          backgroundColor: COLORS.primary,
        },
        headerTintColor: COLORS.white,
        headerTitleStyle: {
          fontWeight: 'bold',
        },
      }}
    >
      <Stack.Screen
        name="Main"
        component={MainTabs}
        options={{ headerShown: false }}
      />
      <Stack.Screen
        name="ContractDetails"
        component={ContractDetailsScreen}
        options={{ title: 'Détails du contrat' }}
      />
      <Stack.Screen
        name="InvoiceDetails"
        component={InvoiceDetailsScreen}
        options={{ title: 'Détails de la facture' }}
      />
      <Stack.Screen
        name="EditProfile"
        component={EditProfileScreen}
        options={{ title: 'Modifier le profil' }}
      />
      <Stack.Screen
        name="ChangePassword"
        component={ChangePasswordScreen}
        options={{ title: 'Changer le mot de passe' }}
      />
      <Stack.Screen
        name="Issues"
        component={IssuesScreen}
        options={{ title: 'Mes signalements' }}
      />
      <Stack.Screen
        name="IssueDetails"
        component={IssueDetailsScreen}
        options={{ title: 'Détails du signalement' }}
      />
      <Stack.Screen
        name="CreateIssue"
        component={CreateIssueScreen}
        options={{ title: 'Nouveau signalement' }}
      />
      <Stack.Screen
        name="Loyalty"
        component={LoyaltyScreen}
        options={{ title: 'Programme de fidélité' }}
      />
      <Stack.Screen
        name="Promotions"
        component={PromotionsScreen}
        options={{ title: 'Promotions' }}
      />
      <Stack.Screen
        name="PaymentReminders"
        component={PaymentRemindersScreen}
        options={{ title: 'Rappels de paiement' }}
      />
      <Stack.Screen
        name="SearchBoxes"
        component={SearchBoxesScreen}
        options={{ title: 'Rechercher un box' }}
      />
      <Stack.Screen
        name="BoxDetails"
        component={BoxDetailsScreen}
        options={{ title: 'Détails du box' }}
      />
      <Stack.Screen
        name="Reservations"
        component={ReservationsScreen}
        options={{ title: 'Mes réservations' }}
      />
      <Stack.Screen
        name="TerminateContract"
        component={TerminateContractScreen}
        options={{ title: 'Résiliation de contrat' }}
      />
    </Stack.Navigator>
  );
};

// Root Navigator
const AppNavigator = () => {
  const { isAuthenticated, loading } = useAuth();

  if (loading) {
    return <Loading />;
  }

  return (
    <NavigationContainer>
      {isAuthenticated ? <MainNavigator /> : <AuthNavigator />}
    </NavigationContainer>
  );
};

export default AppNavigator;
