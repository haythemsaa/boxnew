import React from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useAuth } from '../context/AuthContext';
import Card from '../components/Card';
import Button from '../components/Button';
import { COLORS } from '../constants/colors';

const ProfileScreen = ({ navigation }) => {
  const { user, logout } = useAuth();

  const handleLogout = () => {
    Alert.alert(
      'Déconnexion',
      'Êtes-vous sûr de vouloir vous déconnecter ?',
      [
        { text: 'Annuler', style: 'cancel' },
        {
          text: 'Déconnexion',
          style: 'destructive',
          onPress: async () => {
            await logout();
          },
        },
      ]
    );
  };

  return (
    <SafeAreaView style={styles.container}>
      <ScrollView style={styles.scrollView}>
        {/* User Info */}
        <Card>
          <Text style={styles.name}>{user?.name || 'Client'}</Text>
          {user?.customer_number && (
            <Text style={styles.customerNumber}>N° {user.customer_number}</Text>
          )}
          {user?.email && (
            <Text style={styles.email}>{user.email}</Text>
          )}
          {user?.phone && (
            <Text style={styles.phone}>{user.phone}</Text>
          )}
        </Card>

        {/* Address */}
        {user?.address && (
          <Card>
            <Text style={styles.sectionTitle}>Adresse</Text>
            <Text style={styles.text}>{user.address}</Text>
            <Text style={styles.text}>
              {user.postal_code} {user.city}
            </Text>
            {user.country && <Text style={styles.text}>{user.country}</Text>}
          </Card>
        )}

        {/* Account Settings */}
        <Card>
          <Text style={styles.sectionTitle}>Mon compte</Text>

          <TouchableOpacity
            style={styles.menuItem}
            onPress={() => navigation.navigate('EditProfile')}
          >
            <Text style={styles.menuText}>Modifier le profil</Text>
            <Text style={styles.menuArrow}>›</Text>
          </TouchableOpacity>

          <View style={styles.divider} />

          <TouchableOpacity
            style={styles.menuItem}
            onPress={() => navigation.navigate('ChangePassword')}
          >
            <Text style={styles.menuText}>Changer le mot de passe</Text>
            <Text style={styles.menuArrow}>›</Text>
          </TouchableOpacity>

          <View style={styles.divider} />

          <TouchableOpacity
            style={styles.menuItem}
            onPress={() => navigation.navigate('NotificationSettings')}
          >
            <Text style={styles.menuText}>Notifications</Text>
            <Text style={styles.menuArrow}>›</Text>
          </TouchableOpacity>
        </Card>

        {/* Other Features */}
        <Card>
          <Text style={styles.sectionTitle}>Services</Text>

          <TouchableOpacity
            style={styles.menuItem}
            onPress={() => navigation.navigate('Issues')}
          >
            <Text style={styles.menuText}>Mes signalements</Text>
            <Text style={styles.menuArrow}>›</Text>
          </TouchableOpacity>

          <View style={styles.divider} />

          <TouchableOpacity
            style={styles.menuItem}
            onPress={() => navigation.navigate('PaymentReminders')}
          >
            <Text style={styles.menuText}>Rappels de paiement</Text>
            <Text style={styles.menuArrow}>›</Text>
          </TouchableOpacity>

          <View style={styles.divider} />

          <TouchableOpacity
            style={styles.menuItem}
            onPress={() => navigation.navigate('Loyalty')}
          >
            <Text style={styles.menuText}>Programme de fidélité</Text>
            <Text style={styles.menuArrow}>›</Text>
          </TouchableOpacity>

          <View style={styles.divider} />

          <TouchableOpacity
            style={styles.menuItem}
            onPress={() => navigation.navigate('Promotions')}
          >
            <Text style={styles.menuText}>Promotions</Text>
            <Text style={styles.menuArrow}>›</Text>
          </TouchableOpacity>

          <View style={styles.divider} />

          <TouchableOpacity
            style={styles.menuItem}
            onPress={() => navigation.navigate('Reservations')}
          >
            <Text style={styles.menuText}>Mes réservations</Text>
            <Text style={styles.menuArrow}>›</Text>
          </TouchableOpacity>

          <View style={styles.divider} />

          <TouchableOpacity
            style={styles.menuItem}
            onPress={() => navigation.navigate('SearchBoxes')}
          >
            <Text style={styles.menuText}>Rechercher un box</Text>
            <Text style={styles.menuArrow}>›</Text>
          </TouchableOpacity>
        </Card>

        {/* Logout Button */}
        <View style={styles.logoutContainer}>
          <Button
            title="Se déconnecter"
            onPress={handleLogout}
            variant="danger"
          />
        </View>

        {/* App Info */}
        <Text style={styles.version}>Version 1.0.0</Text>
      </ScrollView>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: COLORS.background,
  },
  scrollView: {
    flex: 1,
    padding: 16,
  },
  name: {
    fontSize: 24,
    fontWeight: 'bold',
    color: COLORS.text.primary,
    marginBottom: 4,
  },
  customerNumber: {
    fontSize: 14,
    color: COLORS.text.secondary,
    marginBottom: 12,
  },
  email: {
    fontSize: 14,
    color: COLORS.text.primary,
    marginBottom: 4,
  },
  phone: {
    fontSize: 14,
    color: COLORS.text.primary,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: COLORS.text.primary,
    marginBottom: 8,
  },
  text: {
    fontSize: 14,
    color: COLORS.text.primary,
    marginBottom: 4,
  },
  menuItem: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingVertical: 12,
  },
  menuText: {
    fontSize: 16,
    color: COLORS.text.primary,
  },
  menuArrow: {
    fontSize: 24,
    color: COLORS.text.secondary,
  },
  divider: {
    height: 1,
    backgroundColor: COLORS.divider,
  },
  logoutContainer: {
    marginTop: 24,
    marginBottom: 16,
  },
  version: {
    fontSize: 12,
    color: COLORS.text.secondary,
    textAlign: 'center',
    marginBottom: 24,
  },
});

export default ProfileScreen;
